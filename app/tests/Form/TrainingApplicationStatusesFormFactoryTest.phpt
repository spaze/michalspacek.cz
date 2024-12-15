<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatus;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatuses;
use MichalSpacekCz\Training\Files\TrainingFiles;
use MichalSpacekCz\Training\Mails\TrainingMailMessageFactory;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Arrays;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TrainingApplicationStatusesFormFactoryTest extends TestCase
{

	private const int APPLICATION_ID = 1337;
	private const int STATUS_ID = 121;

	private UiForm $form;
	private bool|string|null $result = null;


	public function __construct(
		private readonly Database $database,
		private readonly TrainingApplicationStatuses $applicationStatuses,
		private readonly TrainingMailMessageFactory $trainingMailMessageFactory,
		private readonly TrainingFiles $trainingFiles,
		TrainingApplicationStatusesFormFactory $formFactory,
		ApplicationPresenter $applicationPresenter,
		DateTimeMachineFactory $dateTimeFactory,
	) {
		$dateTime = new DateTimeImmutable('2024-12-17 12:10:48');
		$dateTimeFactory->setDateTime($dateTime);
		// For TrainingApplicationStatuses::getStatusId()
		$this->database->addFetchFieldResult(self::STATUS_ID);
		// For TrainingApplicationStatuses::getChildrenStatuses()
		$this->database->addFetchPairsResult([
			15 => TrainingApplicationStatus::Attended->value,
		]);

		$this->form = $formFactory->create(
			function (Html|null $message): void {
				if ($message === null) {
					$this->result = true;
				} else {
					$this->result = $message->toHtml();
				}
			},
			[$this->buildApplication()],
		);
		$applicationPresenter->anchorForm($this->form);
		$this->form->setDefaults([
			'applications' => [
				self::APPLICATION_ID => TrainingApplicationStatus::Attended->value,
			],
		]);
	}


	public function testCreateOnClickSubmit(): void
	{
		$statusDateTime = new DateTime('2024-10-20 05:06:07');
		// For TrainingApplicationStatuses::setStatus()
		$this->database->addFetchResult([
			'statusId' => 15, // REMINDED
			'statusTime' => $statusDateTime,
			'statusTimeTimeZone' => $statusDateTime->getTimezone()->getName(),
		]);
		// For TrainingApplicationStatuses::getDiscardedStatuses()
		$this->database->addFetchFieldResult(self::STATUS_ID + 1);
		// For TrainingApplicationStatuses::getAllowFilesStatuses()
		$this->database->addFetchFieldResult(self::STATUS_ID + 2);
		$this->database->addFetchFieldResult(self::STATUS_ID + 3);
		$this->database->addFetchFieldResult(self::STATUS_ID + 4);
		$submit = $this->form->getComponent('submit');
		assert($submit instanceof SubmitButton);
		Arrays::invoke($submit->onClick);
		Assert::true($this->result);
		Assert::same([
			[
				'key_status' => self::STATUS_ID,
				'status_time' => '2024-12-17 12:10:48',
				'status_time_timezone' => 'Europe/Prague',
			],
		], $this->database->getParamsArrayForQuery('UPDATE training_applications SET ? WHERE id_application = ?'));
	}


	public function testCreateOnClickFamiliar(): void
	{
		$this->addApplicationFetchResult();
		$statusDateTime = new DateTime('2024-10-20 07:08:09');
		// For TrainingApplicationStatuses::setStatus()
		$this->database->addFetchResult([
			'statusId' => 15, // REMINDED
			'statusTime' => $statusDateTime,
			'statusTimeTimeZone' => $statusDateTime->getTimezone()->getName(),
		]);
		$familiar = $this->form->getComponent('familiar');
		assert($familiar instanceof SubmitButton);
		Arrays::invoke($familiar->onClick);
		Assert::same('Tykání nastaveno pro 1 účastníků ve stavu <code>ATTENDED</code>', $this->result);
		Assert::same([self::APPLICATION_ID], $this->database->getParamsForQuery('UPDATE training_applications SET familiar = TRUE WHERE id_application = ?'));
	}


	private function addApplicationFetchResult(): void
	{
		$this->database->addFetchResult([
			'id' => self::APPLICATION_ID,
			'name' => null,
			'email' => null,
			'familiar' => 0,
			'company' => null,
			'street' => null,
			'city' => null,
			'zip' => null,
			'country' => null,
			'companyId' => null,
			'companyTaxId' => null,
			'note' => null,
			'status' => 'ATTENDED',
			'statusTime' => new DateTime(),
			'dateId' => null,
			'trainingId' => null,
			'trainingAction' => 'action',
			'trainingName' => 'Le //Name//',
			'trainingStart' => null,
			'trainingEnd' => null,
			'publicDate' => 1,
			'remote' => 1,
			'remoteUrl' => 'https://remote.example/',
			'remoteNotes' => null,
			'videoHref' => null,
			'feedbackHref' => null,
			'venueAction' => null,
			'venueName' => null,
			'venueNameExtended' => null,
			'venueAddress' => null,
			'venueCity' => null,
			'price' => null,
			'vatRate' => null,
			'priceVat' => null,
			'discount' => null,
			'invoiceId' => null,
			'paid' => null,
			'accessToken' => 'token',
			'sourceAlias' => 'michal-spacek',
			'sourceName' => 'Michal Špaček',
		]);
	}


	private function buildApplication(): TrainingApplication
	{
		return new TrainingApplication(
			$this->applicationStatuses,
			$this->trainingMailMessageFactory,
			$this->trainingFiles,
			self::APPLICATION_ID,
			null,
			null,
			false,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			TrainingApplicationStatus::Reminded,
			new DateTime(),
			true,
			false,
			false,
			null,
			null,
			'action',
			Html::fromText('Name'),
			null,
			null,
			false,
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
			'',
			'',
			null,
			null,
			null,
			'accessToken',
			'michal-spacek',
			'Michal Špaček',
			'MŠ',
		);
	}

}

TestCaseRunner::run(TrainingApplicationStatusesFormFactoryTest::class);
