<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Files;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingFilesTest extends TestCase
{

	public function __construct(
		private readonly TrainingFiles $trainingFiles,
	) {
	}


	public function testIsAllowedExtension(): void
	{
		Assert::true($this->trainingFiles->isAllowedExtension('pdf'));
		Assert::true($this->trainingFiles->isAllowedExtension('PDF'));
		Assert::true($this->trainingFiles->isAllowedExtension('png'));
		Assert::true($this->trainingFiles->isAllowedExtension('jpeg'));
		Assert::false($this->trainingFiles->isAllowedExtension('jpg')); // nette/forms saves an uploaded JPEG as .jpeg, not .jpg
		Assert::false($this->trainingFiles->isAllowedExtension('php'));
		Assert::false($this->trainingFiles->isAllowedExtension('html'));
	}

}

TestCaseRunner::run(TrainingFilesTest::class);
