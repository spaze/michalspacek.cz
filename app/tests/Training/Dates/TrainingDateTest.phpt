<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\Training\TrainingTestDataFactory;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingDateTest extends TestCase
{

	public function __construct(
		private readonly TrainingTestDataFactory $trainingTestDataFactory,
	) {
	}


	public function testGetApplications(): void
	{
		$date = $this->trainingTestDataFactory->getTrainingDate();
		$date->setApplications([
			$this->trainingTestDataFactory->getTrainingApplication(123),
			$this->trainingTestDataFactory->getTrainingApplication(456),
		]);
		Assert::same(123, $date->getApplications()[0]->getId());
		Assert::same(456, $date->getApplications()[1]->getId());
	}


	public function testGetCanceledApplications(): void
	{
		$date = $this->trainingTestDataFactory->getTrainingDate();
		$date->setCanceledApplications([
			$this->trainingTestDataFactory->getTrainingApplication(123),
			$this->trainingTestDataFactory->getTrainingApplication(456),
		]);
		Assert::same(123, $date->getCanceledApplications()[0]->getId());
		Assert::same(456, $date->getCanceledApplications()[1]->getId());
	}


	public function testGetValidApplicationsCount(): void
	{
		$date = $this->trainingTestDataFactory->getTrainingDate();
		$date->setApplications([
			$this->trainingTestDataFactory->getTrainingApplication(123),
			$this->trainingTestDataFactory->getTrainingApplication(456),
			$this->trainingTestDataFactory->getTrainingApplication(789),
		]);
		Assert::same(3, $date->getValidApplicationsCount());
		$date->setApplications([
			$this->trainingTestDataFactory->getTrainingApplication(123),
			$this->trainingTestDataFactory->getTrainingApplication(456),
		]);
		Assert::same(2, $date->getValidApplicationsCount());
	}


	public function testIsAttentionRequired(): void
	{
		$date = $this->trainingTestDataFactory->getTrainingDate(status: TrainingDateStatus::Created);
		Assert::false($date->isAttentionRequired());

		$application = $this->trainingTestDataFactory->getTrainingApplication(404);
		$date = $this->trainingTestDataFactory->getTrainingDate(status: TrainingDateStatus::Created);
		Assert::false($date->isAttentionRequired());
		$date->setApplications([$application]);
		Assert::false($date->isAttentionRequired());

		$date = $this->trainingTestDataFactory->getTrainingDate(status: TrainingDateStatus::Canceled);
		Assert::false($date->isAttentionRequired());
		$date->setCanceledApplications([$application]);
		Assert::true($date->isAttentionRequired());

		$date = $this->trainingTestDataFactory->getTrainingDate(status: TrainingDateStatus::Canceled);
		Assert::false($date->isAttentionRequired());
		$date->setApplications([$application]);
		Assert::true($date->isAttentionRequired());
	}

}

TestCaseRunner::run(TrainingDateTest::class);
