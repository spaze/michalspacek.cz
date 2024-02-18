<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Mails;

use DateTime;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatuses;
use MichalSpacekCz\Training\Files\TrainingFiles;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingMailMessageFactoryTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TrainingMailMessageFactory $trainingMailMessageFactory,
		private readonly TrainingApplicationStatuses $applicationStatuses,
		private readonly TrainingFiles $trainingFiles,
	) {
	}


	public function testGetMailMessage(): void
	{
		$application = $this->getApplication();
		Assert::exception(function () use ($application): void {
			$this->trainingMailMessageFactory->getMailMessage($application);
		}, ShouldNotHappenException::class, "Unsupported next status: '<null>'");

		$application->setNextStatus(TrainingApplicationStatuses::STATUS_INVITED);
		Assert::same('invitation', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application->setNextStatus(TrainingApplicationStatuses::STATUS_MATERIALS_SENT);
		Assert::same('materials', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application = $this->getApplication(true);
		$application->setNextStatus(TrainingApplicationStatuses::STATUS_MATERIALS_SENT);
		Assert::same('materialsFamiliar', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application->setNextStatus(TrainingApplicationStatuses::STATUS_INVOICE_SENT);
		Assert::same('invoice', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application = $this->getApplication(status: TrainingApplicationStatuses::STATUS_PRO_FORMA_INVOICE_SENT);
		$application->setNextStatus(TrainingApplicationStatuses::STATUS_INVOICE_SENT);
		Assert::same('invoiceAfterProforma', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application->setNextStatus(TrainingApplicationStatuses::STATUS_INVOICE_SENT_AFTER);
		Assert::same('invoiceAfter', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application->setNextStatus(TrainingApplicationStatuses::STATUS_REMINDED);
		Assert::exception(function () use ($application): void {
			$this->trainingMailMessageFactory->getMailMessage($application);
		}, ShouldNotHappenException::class, "Training application id '1' with next status 'REMINDED' should have both training start and end set");

		$application = $this->getApplication(trainingStart: new DateTime(), trainingEnd: new DateTime());
		$application->setNextStatus(TrainingApplicationStatuses::STATUS_REMINDED);
		Assert::same('reminder', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application = $this->getApplication(isRemote: true, trainingStart: new DateTime(), trainingEnd: new DateTime());
		$application->setNextStatus(TrainingApplicationStatuses::STATUS_REMINDED);
		Assert::same('reminderRemote', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());
	}


	public function testGetMailMessageInvoiceAfterProforma(): void
	{
		$this->database->addFetchAllResult([
			[
				'id' => 1,
				'statusId' => 2,
				'status' => TrainingApplicationStatuses::STATUS_PRO_FORMA_INVOICE_SENT,
				'statusTime' => new DateTime(),
				'statusTimeTimeZone' => 'Europe/Prague',
			],
		]);
		$application = $this->getApplication();
		$application->setNextStatus(TrainingApplicationStatuses::STATUS_INVOICE_SENT_AFTER);
		Assert::same('invoiceAfterProforma', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());
	}


	private function getApplication(bool $familiar = false, string $status = 'ATTENDED', bool $isRemote = false, ?DateTime $trainingStart = null, ?DateTime $trainingEnd = null): TrainingApplication
	{
		return new TrainingApplication(
			$this->applicationStatuses,
			$this->trainingMailMessageFactory,
			$this->trainingFiles,
			1,
			null,
			null,
			$familiar,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			$status,
			new DateTime(),
			true,
			false,
			false,
			null,
			null,
			'action',
			Html::fromText('Name'),
			$trainingStart,
			$trainingEnd,
			false,
			$isRemote,
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

TestCaseRunner::run(TrainingMailMessageFactoryTest::class);
