<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Mails;

use DateTime;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\Training\TrainingTestDataFactory;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatus;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingMailMessageFactoryTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TrainingMailMessageFactory $trainingMailMessageFactory,
		private readonly TrainingTestDataFactory $dataFactory,
	) {
	}


	public function testGetMailMessage(): void
	{
		$application = $this->dataFactory->getTrainingApplication(1, null, null);
		Assert::exception(function () use ($application): void {
			$this->trainingMailMessageFactory->getMailMessage($application);
		}, ShouldNotHappenException::class, "Unsupported next status: '<null>'");

		$application->setNextStatus(TrainingApplicationStatus::Invited);
		Assert::same('invitation', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application->setNextStatus(TrainingApplicationStatus::MaterialsSent);
		Assert::same('materials', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application = $this->dataFactory->getTrainingApplication(1, familiar: true);
		$application->setNextStatus(TrainingApplicationStatus::MaterialsSent);
		Assert::same('materialsFamiliar', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application->setNextStatus(TrainingApplicationStatus::InvoiceSent);
		Assert::same('invoice', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application = $this->dataFactory->getTrainingApplication(1, status: TrainingApplicationStatus::ProFormaInvoiceSent);
		$application->setNextStatus(TrainingApplicationStatus::InvoiceSent);
		Assert::same('invoiceAfterProforma', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application->setNextStatus(TrainingApplicationStatus::InvoiceSentAfter);
		Assert::same('invoiceAfter', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application->setNextStatus(TrainingApplicationStatus::Reminded);
		Assert::exception(function () use ($application): void {
			$this->trainingMailMessageFactory->getMailMessage($application);
		}, ShouldNotHappenException::class, "Training application id '1' with next status 'REMINDED' should have both training start and end set");

		$application = $this->dataFactory->getTrainingApplication(1, trainingStart: new DateTime(), trainingEnd: new DateTime());
		$application->setNextStatus(TrainingApplicationStatus::Reminded);
		Assert::same('reminder', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application = $this->dataFactory->getTrainingApplication(1, trainingStart: new DateTime(), trainingEnd: new DateTime(), remote: true);
		$application->setNextStatus(TrainingApplicationStatus::Reminded);
		Assert::same('reminderRemote', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());
	}


	public function testGetMailMessageInvoiceAfterProforma(): void
	{
		$this->database->addFetchAllResult([
			[
				'id' => 1,
				'statusId' => 2,
				'status' => TrainingApplicationStatus::ProFormaInvoiceSent->value,
				'statusTime' => new DateTime(),
				'statusTimeTimeZone' => 'Europe/Prague',
			],
		]);
		$application = $this->dataFactory->getTrainingApplication(1, null, null);
		$application->setNextStatus(TrainingApplicationStatus::InvoiceSentAfter);
		Assert::same('invoiceAfterProforma', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());
	}

}

TestCaseRunner::run(TrainingMailMessageFactoryTest::class);
