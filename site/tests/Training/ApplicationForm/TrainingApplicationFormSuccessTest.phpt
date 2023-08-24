<?php
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationForm;

use DateTime;
use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\NullSession;
use MichalSpacekCz\Test\NullMailer;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\TrainingDateStatus;
use Nette\Application\Application;
use Nette\Application\IPresenterFactory;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Http\SessionSection;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingApplicationFormSuccessTest extends TestCase
{

	private const DATE_ID = 1337;
	private const APPLICATION_ID = 808;
	private const TRAINING_ACTION = 'training-action';
	private const NAME = 'Foo';
	private const EMAIL = 'foo@example.com';
	private const COMPANY = 'A-Team';
	private const STREET = '21 Jump';
	private const CITY = 'York';
	private const ZIP = '13371';
	private const COUNTRY = 'cz';
	private const COMPANY_ID = '123';
	private const COMPANY_TAX_ID = 'CZ123';
	private const NOTE = 'book';


	private ?string $onSuccessAction = null;
	private ?string $onErrorMessage = null;
	private Form $form;
	private SessionSection $sessionSection;


	public function __construct(
		private readonly Database $database,
		private readonly TrainingApplicationFormSuccess $formSuccess,
		private readonly NullMailer $mailer,
		TrainingControlsFactory $trainingControlsFactory,
		IPresenterFactory $presenterFactory,
		Application $application,
		NullSession $session,
	) {
		$presenter = $presenterFactory->createPresenter('Www:Homepage'); // Has to be a real presenter that extends Ui\Presenter
		if (!$presenter instanceof Presenter) {
			throw new ShouldNotHappenException();
		}
		PrivateProperty::setValue($application, 'presenter', $presenter);
		$this->form = new Form($presenter, 'form');
		$this->form->addSelect('trainingId', 'Date', [self::DATE_ID => 'Training']);
		$trainingControlsFactory->addAttendee($this->form);
		$trainingControlsFactory->addCompany($this->form);
		$trainingControlsFactory->addNote($this->form);
		$trainingControlsFactory->addCountry($this->form);
		$this->form->setDefaults([
			'trainingId' => self::DATE_ID,
			'name' => self::NAME,
			'email' => self::EMAIL,
			'company' => self::COMPANY,
			'companyId' => self::COMPANY_ID,
			'companyTaxId' => self::COMPANY_TAX_ID,
			'street' => self::STREET,
			'city' => self::CITY,
			'zip' => self::ZIP,
			'note' => self::NOTE,
			'country' => self::COUNTRY,
		]);
		$this->sessionSection = $session->getSection('section');
	}


	protected function setUp(): void
	{
		$this->onSuccessAction = null;
		$this->onErrorMessage = null;

		$this->database->setFetchPairsResult([ // For Statuses::getInitialStatuses()
			4 => 'SIGNED_UP',
		]);
		$this->database->addFetchFieldResult(1); // For Statuses::getStatusId() in TrainingApplicationStorage::insertApplication()
		$this->database->addFetchFieldResult(303); // For TrainingApplicationSources::getSourceId in TrainingApplicationStorage::insertApplication()
		$this->database->addFetchFieldResult(4); // For Statuses::getStatusId() in Statuses::setStatus()
		$this->database->setFetchResult([ // For $prevStatus in Statuses::setStatus()
			'statusId' => 1,
			'statusTime' => new DateTime('-2 days'),
			'statusTimeTimeZone' => 'Europe/Prague',
		]);
	}


	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testSuccessDateNotUpcoming(): void
	{
		$this->callSuccess([
			self::DATE_ID + 1 => $this->buildTrainingDate(self::DATE_ID + 1),
			self::DATE_ID + 2 => $this->buildTrainingDate(self::DATE_ID + 2),
		]);
		Assert::null($this->onSuccessAction);
		Assert::same('messages.trainings.wrongdateapplication', $this->onErrorMessage);
		Assert::count(0, iterator_to_array($this->sessionSection->getIterator()));
	}


	public function testSuccessAddApplication(): void
	{
		$this->callSuccess();
		Assert::same(self::TRAINING_ACTION, $this->onSuccessAction);
		Assert::null($this->onErrorMessage);

		$params = $this->database->getParamsArrayForQuery('INSERT INTO training_applications');
		Assert::same(self::DATE_ID, $params[0]['key_date']);

		$this->assertSessionSection();

		Assert::same([self::EMAIL => self::NAME], $this->mailer->getMail()->getHeader('To'));
	}


	public function testSuccessUpdateApplication(): void
	{
		$this->sessionSection->set('application', [
			self::TRAINING_ACTION => ['dateId' => self::DATE_ID, 'id' => self::APPLICATION_ID],
			'foo' => 'bar',
		]);

		$this->callSuccess();
		Assert::same(self::TRAINING_ACTION, $this->onSuccessAction);
		Assert::null($this->onErrorMessage);

		$query = 'UPDATE training_applications SET ? WHERE id_application = ?';
		$params = $this->database->getParamsArrayForQuery($query);
		Assert::same(self::NAME, $params[0]['name']);
		$whereParams = $this->database->getParamsForQuery($query);
		Assert::same(self::APPLICATION_ID, $whereParams[0]);

		$this->assertSessionSection();

		$application = $this->sessionSection->get('application');
		if (is_array($application)) {
			Assert::null($application[self::TRAINING_ACTION]);
			Assert::same('bar', $application['foo']);
		} else {
			Assert::fail('Application in session section is of a wrong type ' . get_debug_type($application));
		}
	}


	/**
	 * @param array<int, TrainingDate>|null $dates
	 */
	private function callSuccess(?array $dates = null): void
	{
		if ($dates === null) {
			$dates = [self::DATE_ID => $this->buildTrainingDate(self::DATE_ID)];
		}
		$this->formSuccess->success(
			$this->form,
			function (string $action): void {
				$this->onSuccessAction = $action;
			},
			function (string $message): void {
				$this->onErrorMessage = $message;
			},
			self::TRAINING_ACTION,
			Html::fromText('name'),
			$dates,
			count($dates) > 1,
			$this->sessionSection,
		);
	}


	private function buildTrainingDate(int $id): TrainingDate
	{
		return new TrainingDate(
			$id,
			'',
			1,
			false,
			false,
			new DateTime(),
			new DateTime(),
			null,
			null,
			true,
			TrainingDateStatus::Confirmed,
			'',
			false,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			false,
			null,
			false,
			null,
			null,
			null,
			null,
		);
	}


	private function assertSessionSection(): void
	{
		Assert::same(self::DATE_ID, $this->sessionSection->get('trainingId'));
		Assert::same(self::NAME, $this->sessionSection->get('name'));
		Assert::same(self::EMAIL, $this->sessionSection->get('email'));
		Assert::same(self::COMPANY, $this->sessionSection->get('company'));
		Assert::same(self::STREET, $this->sessionSection->get('street'));
		Assert::same(self::CITY, $this->sessionSection->get('city'));
		Assert::same(self::ZIP, $this->sessionSection->get('zip'));
		Assert::same(self::COUNTRY, $this->sessionSection->get('country'));
		Assert::same(self::COMPANY_ID, $this->sessionSection->get('companyId'));
		Assert::same(self::COMPANY_TAX_ID, $this->sessionSection->get('companyTaxId'));
		Assert::same(self::NOTE, $this->sessionSection->get('note'));
	}

}

$runner->run(TrainingApplicationFormSuccessTest::class);
