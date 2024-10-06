<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Trainings;

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
	}

}

TestCaseRunner::run(TrainingsTest::class);
