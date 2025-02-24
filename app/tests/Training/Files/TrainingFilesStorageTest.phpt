<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Files;

use DateTimeImmutable;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingFilesStorageTest extends TestCase
{

	private readonly TrainingFilesStorage $trainingFilesStorage;


	public function __construct()
	{
		$this->trainingFilesStorage = new TrainingFilesStorage();
	}


	public function testGetFilesDir(): void
	{
		Assert::noError(function (): void {
			$dir = $this->trainingFilesStorage->getFilesDir(new DateTimeImmutable('2020-10-20 20:30:40'));
			Assert::match('~^.{10,}/files/trainings/2020-10-20/$~', $dir);
		});
	}

}

TestCaseRunner::run(TrainingFilesStorageTest::class);
