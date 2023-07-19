<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Training\Applications\TrainingApplications;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingApplicationsTest extends TestCase
{

	public function __construct(
		private readonly TrainingApplications $trainingApplications,
		private readonly Database $database,
	) {
	}


	public function testGetValidUnpaidCount(): void
	{
		$this->database->setFetchFieldDefaultResult(909);
		Assert::same(909, $this->trainingApplications->getValidUnpaidCount());

		$this->database->setFetchFieldDefaultResult('\o/');
		Assert::exception(function (): void {
			$this->trainingApplications->getValidUnpaidCount();
		}, ShouldNotHappenException::class, 'Count is a string not an integer');
	}

}

$runner->run(TrainingApplicationsTest::class);
