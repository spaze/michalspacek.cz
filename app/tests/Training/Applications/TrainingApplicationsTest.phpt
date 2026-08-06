<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingApplicationsTest extends TestCase
{

	public function __construct(
		private readonly TrainingApplications $trainingApplications,
		private readonly Database $database,
		private readonly NullLogger $logger,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGetValidUnpaidCount(): void
	{
		$this->database->setFetchFieldDefaultResult(909);
		Assert::same(909, $this->trainingApplications->getValidUnpaidCount());
	}


	public function testGetApplicationByTokenAsksForTheTokenItWasGiven(): void
	{
		// The token is the whole authorization here, there is no session behind it: whoever holds the link is
		// shown the application, so the query has to be scoped by the token that was handed in
		Assert::null($this->trainingApplications->getApplicationByToken('a-visitors-token'));

		Assert::same(
			['a-visitors-token', 'cs_CZ'],
			$this->database->getParamsForQueryContaining('a.access_token = ?'),
		);
	}


	public function testDeleteMultiple(): void
	{
		$this->trainingApplications->deleteMultiple([1, 2]);
		Assert::same([[1, 2]], $this->database->getParamsArrayForQuery('DELETE FROM training_applications WHERE id_application IN (?)'));
		Assert::same(['Deleting applications: 1 2'], $this->logger->getLogged());
	}

}

TestCaseRunner::run(TrainingApplicationsTest::class);
