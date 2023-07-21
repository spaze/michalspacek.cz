<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Mails;

use DateTime;
use MichalSpacekCz\DateTime\DateTimeFormatter;
use MichalSpacekCz\DateTime\DateTimeZoneFactory;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\Files\TrainingFiles;
use MichalSpacekCz\Training\Statuses;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingMailMessageFactoryTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TrainingMailMessageFactory $trainingMailMessageFactory,
		private readonly Statuses $trainingStatuses,
		private readonly TrainingFiles $trainingFiles,
		private readonly DateTimeZoneFactory $dateTimeZoneFactory,
		private readonly DateTimeFormatter $dateTimeFormatter,
	) {
	}


	public function testGetMailMessage(): void
	{
		$application = $this->getApplication();
		Assert::exception(function () use ($application): void {
			$this->trainingMailMessageFactory->getMailMessage($application);
		}, ShouldNotHappenException::class, "Unsupported next status: '<null>'");

		$application->setNextStatus(Statuses::STATUS_INVITED);
		Assert::same('invitation', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application->setNextStatus(Statuses::STATUS_MATERIALS_SENT);
		Assert::same('materials', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application = $this->getApplication(true);
		$application->setNextStatus(Statuses::STATUS_MATERIALS_SENT);
		Assert::same('materialsFamiliar', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application->setNextStatus(Statuses::STATUS_INVOICE_SENT);
		Assert::same('invoice', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application = $this->getApplication(status: Statuses::STATUS_PRO_FORMA_INVOICE_SENT);
		$application->setNextStatus(Statuses::STATUS_INVOICE_SENT);
		Assert::same('invoiceAfterProforma', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application->setNextStatus(Statuses::STATUS_INVOICE_SENT_AFTER);
		Assert::same('invoiceAfter', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application->setNextStatus(Statuses::STATUS_REMINDED);
		Assert::exception(function () use ($application): void {
			$this->trainingMailMessageFactory->getMailMessage($application);
		}, ShouldNotHappenException::class, "Training application id '1' with next status 'REMINDED' should have both training start and end set");

		$application = $this->getApplication(trainingStart: new DateTime(), trainingEnd: new DateTime());
		$application->setNextStatus(Statuses::STATUS_REMINDED);
		Assert::same('reminder', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());

		$application = $this->getApplication(isRemote: true, trainingStart: new DateTime(), trainingEnd: new DateTime());
		$application->setNextStatus(Statuses::STATUS_REMINDED);
		Assert::same('reminderRemote', $this->trainingMailMessageFactory->getMailMessage($application)->getBasename());
	}


	public function testGetMailMessageInvoiceAfterProforma(): void
	{
		$trainingStatuses = new Statuses($this->database, $this->dateTimeZoneFactory);
		$factory = new TrainingMailMessageFactory($trainingStatuses, $this->dateTimeFormatter);
		$this->database->addFetchAllResult([
			[
				'id' => 1,
				'statusId' => 2,
				'status' => Statuses::STATUS_PRO_FORMA_INVOICE_SENT,
				'statusTime' => new DateTime(),
				'statusTimeTimeZone' => 'Europe/Prague',
			],
		]);
		$application = $this->getApplication();
		$application->setNextStatus(Statuses::STATUS_INVOICE_SENT_AFTER);
		Assert::same('invoiceAfterProforma', $factory->getMailMessage($application)->getBasename());
	}


	private function getApplication(bool $familiar = false, string $status = 'ATTENDED', bool $isRemote = false, ?DateTime $trainingStart = null, ?DateTime $trainingEnd = null): TrainingApplication
	{
		return new TrainingApplication(
			$this->trainingStatuses,
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

$runner->run(TrainingMailMessageFactoryTest::class);
