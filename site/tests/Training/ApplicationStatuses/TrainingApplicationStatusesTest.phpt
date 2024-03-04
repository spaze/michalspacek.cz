<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationStatuses;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingApplicationStatusesTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TrainingApplicationStatuses $applicationStatuses,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		PrivateProperty::setValue($this->applicationStatuses, 'statusIds', []);
	}


	public function testGetStatusId(): void
	{
		$this->database->setFetchFieldDefaultResult(303);
		Assert::same(303, $this->applicationStatuses->getStatusId(TrainingApplicationStatus::SignedUp));

		$this->database->setFetchFieldDefaultResult('nah, it cached');
		Assert::same(303, $this->applicationStatuses->getStatusId(TrainingApplicationStatus::SignedUp));
	}

}

TestCaseRunner::run(TrainingApplicationStatusesTest::class);
