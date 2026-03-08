<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\Training\TrainingTestDataFactory;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatus;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingApplicationTest extends TestCase
{

	public function __construct(
		private readonly TrainingTestDataFactory $testDataFactory,
	) {
	}


	public function testGetMailMessage(): void
	{
		$application = $this->testDataFactory->getTrainingApplication(303);
		$application->setNextStatus(TrainingApplicationStatus::Invited);
		Assert::same('Pozvánka na školení Training Name', $application->getMailMessage()->getSubject());
	}

}

TestCaseRunner::run(TrainingApplicationTest::class);
