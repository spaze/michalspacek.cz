<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Files;

use DateTimeImmutable;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingFilesStorageTest extends TestCase
{

	public function __construct(
		private readonly TrainingFilesStorage $trainingFilesStorage,
	) {
	}


	public function testGetFilesDir(): void
	{
		Assert::noError(function () use (&$dir): void {
			$dir = $this->trainingFilesStorage->getFilesDir(new DateTimeImmutable('2020-10-20 20:30:40'));
		});
		Assert::match('~^.{10,}/files/trainings/2020-10-20/$~', $dir);
	}

}

$runner->run(TrainingFilesStorageTest::class);
