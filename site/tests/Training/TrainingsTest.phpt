<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\Database\Database;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

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
		$this->database->setFetchFieldResult('pulled pork');
		Assert::same('pulled pork', $this->trainings->getActionById(303));

		$this->database->setFetchFieldResult(808);
		Assert::exception(function (): void {
			$this->trainings->getActionById(303);
		}, ShouldNotHappenException::class, "Action for id '303' is a int not a string");
	}

}

$runner->run(TrainingsTest::class);
