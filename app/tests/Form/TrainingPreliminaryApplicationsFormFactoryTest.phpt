<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace Form;

use MichalSpacekCz\Form\TrainingPreliminaryApplicationsFormFactory;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Database\DatabaseTransactionStatus;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\Training\TrainingTestDataFactory;
use MichalSpacekCz\Training\Preliminary\PreliminaryTraining;
use Nette\Utils\Arrays;
use Override;
use PDOException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class TrainingPreliminaryApplicationsFormFactoryTest extends TestCase
{

	private ?bool $result = null;

	private UiForm $form;


	public function __construct(
		private readonly Database $database,
		private readonly NullLogger $logger,
		TrainingPreliminaryApplicationsFormFactory $formFactory,
		ApplicationPresenter $applicationPresenter,
		TrainingTestDataFactory $dataFactory,
	) {
		$training1 = new PreliminaryTraining(303, 'action-303', 'Training 303');
		$training1->addApplication($dataFactory->getTrainingApplication(3031));
		$training1->addApplication($dataFactory->getTrainingApplication(3032));
		$training2 = new PreliminaryTraining(808, 'action-808', 'Training 808');
		$training2->addApplication($dataFactory->getTrainingApplication(8081));
		$training2->addApplication($dataFactory->getTrainingApplication(8082));
		$trainings = [
			$training1,
			$training2,
		];
		$this->form = $formFactory->create(
			$trainings,
			function (): void {
				$this->result = true;
			},
		);
		$applicationPresenter->anchorForm($this->form);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->result = null;
		$this->database->reset();
		$this->logger->reset();
	}


	public function testCreateOnSuccessNoApplications(): void
	{
		$this->form->setDefaults(['applications' => []]);
		Arrays::invoke($this->form->onSuccess, $this->form);
		Assert::null($this->result);
		Assert::same(DatabaseTransactionStatus::None, $this->database->transactionStatus);
		Assert::same([], $this->logger->getLogged());
	}


	public function testCreateOnSuccessNoneSelected(): void
	{
		$this->form->setDefaults([
			'applications' => [
				'3031' => false,
				'3032' => false,
				'8081' => false,
				'8082' => false,
			],
		]);
		Arrays::invoke($this->form->onSuccess, $this->form);
		Assert::null($this->result);
		Assert::same(DatabaseTransactionStatus::None, $this->database->transactionStatus);
		Assert::same([], $this->logger->getLogged());
	}


	public function testCreateOnSuccess(): void
	{
		$this->form->setDefaults([
			'applications' => [
				'3031' => true,
				'3032' => false,
				'8081' => false,
				'8082' => true,
			],
		]);
		Arrays::invoke($this->form->onSuccess, $this->form);
		Assert::true($this->result);
		Assert::same(DatabaseTransactionStatus::Committed, $this->database->transactionStatus);
		Assert::same(['Deleting all status history records for applications: 3031 8082', 'Deleting applications: 3031 8082'], $this->logger->getLogged());
	}


	public function testCreateOnSuccessDatabaseFailure(): void
	{
		$this->database->willThrow(new PDOException());
		$this->form->setDefaults([
			'applications' => [
				'3031' => true,
				'3032' => false,
				'8081' => false,
				'8082' => false,
			],
		]);
		Arrays::invoke($this->form->onSuccess, $this->form);
		Assert::null($this->result);
		Assert::same(DatabaseTransactionStatus::RolledBack, $this->database->transactionStatus);
		$logged = $this->logger->getLogged();
		Assert::same('Deleting all status history records for applications: 3031', $logged[0]);
		Assert::type(PDOException::class, $logged[1]);
	}

}

TestCaseRunner::run(TrainingPreliminaryApplicationsFormFactoryTest::class);
