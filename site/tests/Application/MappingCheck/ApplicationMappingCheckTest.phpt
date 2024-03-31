<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application\MappingCheck;

use MichalSpacekCz\Application\MappingCheck\Exceptions\ApplicationMappingMismatchException;
use MichalSpacekCz\Application\MappingCheck\Exceptions\ApplicationMappingMultiplePrimaryFilesException;
use MichalSpacekCz\Application\MappingCheck\Exceptions\ApplicationMappingNoOtherFilesException;
use MichalSpacekCz\Application\MappingCheck\Exceptions\ApplicationMappingNoPrimaryFileException;
use MichalSpacekCz\Application\MappingCheck\Files\ApplicationMappingCheckFile;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class ApplicationMappingCheckTest extends TestCase
{

	public function testCheckFilesMultiplePrimaryFiles(): void
	{
		$check = new ApplicationMappingCheck([
			$this->buildFile('file1', true, []),
			$this->buildFile('file2', true, []),
		]);
		Assert::exception(function () use ($check): void {
			$check->checkFiles();
		}, ApplicationMappingMultiplePrimaryFilesException::class, "Application mapping has multiple primary files: 'file1' & 'file2'");
	}


	public function testCheckFilesNoPrimaryFile(): void
	{
		$check = new ApplicationMappingCheck([
			$this->buildFile('file1', false, []),
			$this->buildFile('file2', false, []),
		]);
		Assert::exception(function () use ($check): void {
			$check->checkFiles();
		}, ApplicationMappingNoPrimaryFileException::class, "Application mapping has no primary file: 'file1', 'file2'");
	}


	public function testCheckFilesNoOtherFiles(): void
	{
		$check = new ApplicationMappingCheck([
			$this->buildFile('file1', true, []),
		]);
		Assert::exception(function () use ($check): void {
			$check->checkFiles();
		}, ApplicationMappingNoOtherFilesException::class, "No other files with application mapping, just the primary one: 'file1'");
	}


	public function testCheckFilesMismatch(): void
	{
		$check = new ApplicationMappingCheck([
			$this->buildFile('file1', true, ['*' => 'foo', 'baz' => 'waldo']),
			$this->buildFile('file2', false, ['*' => 'bar', 'baz' => 'waldo']),
		]);
		Assert::exception(function () use ($check): void {
			$check->checkFiles();
		}, ApplicationMappingMismatchException::class, "Application mapping in 'file2' ('*: bar; baz: waldo') doesn't match the primary mapping in 'file1' ('*: foo; baz: waldo')");
	}


	public function testCheckFiles(): void
	{
		$mapping = ['*' => 'foo', 'baz' => 'waldo'];
		$check = new ApplicationMappingCheck([
			$this->buildFile('file0', false, $mapping),
			$this->buildFile('file1-primary', true, $mapping),
			$this->buildFile('file2', false, $mapping),
		]);
		Assert::same(['file1-primary', 'file0', 'file2'], $check->checkFiles());
	}


	/**
	 * @param array<string, string> $mapping
	 */
	private function buildFile(string $filename, bool $isPrimary, array $mapping): ApplicationMappingCheckFile
	{
		return new readonly class ($filename, $isPrimary, $mapping) implements ApplicationMappingCheckFile
		{

			/**
			 * @param array<string, string> $mapping
			 */
			public function __construct(
				private string $filename,
				private bool $isPrimary,
				private array $mapping,
			) {
			}


			#[Override]
			public function getFilename(): string
			{
				return $this->filename;
			}


			#[Override]
			public function isPrimaryFile(): bool
			{
				return $this->isPrimary;
			}


			#[Override]
			public function getMapping(): array
			{
				return $this->mapping;
			}

		};
	}

}

TestCaseRunner::run(ApplicationMappingCheckTest::class);
