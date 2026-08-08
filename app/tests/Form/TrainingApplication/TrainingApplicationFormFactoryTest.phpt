<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\TrainingApplication;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\Training\TrainingTestDataFactory;
use MichalSpacekCz\Training\Applications\TrainingApplicationSessionSection;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;
use Nette\Http\Session;
use Nette\Utils\Html;
use Override;
use stdClass;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/*
 * What half-filled applications are restored from, so the keys the session stores under and the names of the
 * controls have to agree. They are set in two different classes and nothing else checks that they match: rename
 * a control and the form silently stops being prefilled, which nobody notices until an applicant retypes their
 * address.
 */

/** @testCase */
final class TrainingApplicationFormFactoryTest extends TestCase
{

	private const string ATTENDEE_NAME = 'Patrick Star';
	private const string EMAIL = 'patrick@example.com';


	private readonly TrainingApplicationSessionSection $sessionSection;
	private ?string $action = null;
	private ?string $message = null;


	public function __construct(
		private readonly TrainingApplicationFormFactory $formFactory,
		private readonly TrainingTestDataFactory $dataFactory,
		private readonly ApplicationPresenter $applicationPresenter,
		Session $sessionHandler,
	) {
		$this->sessionSection = $sessionHandler->getSection('training', TrainingApplicationSessionSection::class);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->sessionSection->removeApplicationValues();
	}


	public function testFormIsPrefilledFromTheSession(): void
	{
		$values = new stdClass();
		$values->attendeeName = self::ATTENDEE_NAME;
		$values->email = self::EMAIL;
		$values->company = 'Krusty Krab';
		$values->street = 'Conch Street 1';
		$values->city = 'Bikini Bottom';
		$values->zip = '12345';
		$values->country = 'cz';
		$values->companyId = '123';
		$values->companyTaxId = 'CZ123';
		$values->note = 'no pickles';
		$this->sessionSection->setOnSuccess($this->dataFactory->getTrainingDate(remote: true), $values);

		$form = $this->createForm();

		Assert::same(self::ATTENDEE_NAME, $this->valueOf($form, 'attendeeName'));
		Assert::same(self::EMAIL, $this->valueOf($form, 'email'));
		Assert::same('Bikini Bottom', $this->valueOf($form, 'city'));
	}


	public function testFormIsEmptyWithoutASession(): void
	{
		$form = $this->createForm();

		Assert::same('', $this->valueOf($form, 'attendeeName'));
		Assert::same('', $this->valueOf($form, 'email'));
		Assert::null($this->action); // building the form doesn't run the handlers
		Assert::null($this->message);
	}


	private function createForm(): Form
	{
		$form = $this->formFactory->create(
			function (string $action): void {
				$this->action = $action;
			},
			function (string $message): void {
				$this->message = $message;
			},
			'action',
			Html::el()->setText('Training'),
			[$this->dataFactory->getTrainingDate(remote: true)],
			$this->sessionSection,
		);
		$this->applicationPresenter->anchorForm($form);
		return $form;
	}


	private function valueOf(Form $form, string $name): mixed
	{
		$control = $form->getComponent($name);
		assert($control instanceof BaseControl);
		return $control->getValue();
	}

}

TestCaseRunner::run(TrainingApplicationFormFactoryTest::class);
