<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Trainings;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingsTest extends TestCase
{

	public function __construct(
		private readonly Trainings $trainings,
		private readonly Database $database,
	) {
	}


	public function testGetActionById(): void
	{
		$this->database->setFetchFieldDefaultResult('pulled pork');
		Assert::same('pulled pork', $this->trainings->getActionById(303));

		$this->database->setFetchFieldDefaultResult(808);
		Assert::exception(function (): void {
			$this->trainings->getActionById(303);
		}, ShouldNotHappenException::class, "Action for id '303' is a int not a string");
	}

}

TestCaseRunner::run(TrainingsTest::class);
