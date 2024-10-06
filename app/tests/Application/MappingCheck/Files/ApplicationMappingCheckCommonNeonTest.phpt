<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application\MappingCheck\Files;

use MichalSpacekCz\Application\MappingCheck\Exceptions\ApplicationMappingFileNotFoundException;
use MichalSpacekCz\Application\MappingCheck\Exceptions\ApplicationMappingInvalidConfigException;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\FileMock;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
class ApplicationMappingCheckCommonNeonTest extends TestCase
{

	public function testFileNotFound(): void
	{
		Assert::exception(function (): void {
			new ApplicationMappingCheckCommonNeon('./foo');
		}, ApplicationMappingFileNotFoundException::class, "Application mapping file not found: './foo'");
	}


	public function testIsPrimaryFile(): void
	{
		$file = FileMock::create();
		Assert::true((new ApplicationMappingCheckCommonNeon($file))->isPrimaryFile());
	}


	/**
	 * @return list<array{0:string, 1:string}>
	 */
	public function getConfig(): array
	{
		return [
			[
				'foo',
				"Application mapping config invalid in 'mock://2.neon': Should be an array, but it's string",
			],
			[
				'- foo',
				"Application mapping config invalid in 'mock://3.neon': Missing 'application' key",
			],
			[
				'application: foo',
				"Application mapping config invalid in 'mock://4.neon': The 'application' key should be an array, but it's string",
			],
			[
				"application:\n - foo",
				"Application mapping config invalid in 'mock://5.neon': Missing 'application.mapping' key",
			],
			[
				"application:\n mapping: foo",
				"Application mapping config invalid in 'mock://6.neon': The 'application.mapping' should be an array, but it's string",
			],
		];
	}


	/** @dataProvider getConfig */
	public function testGetMappingInvalidConfig(string $config, string $exception): void
	{
		$file = FileMock::create($config, 'neon');
		Assert::exception(function () use ($file): void {
			(new ApplicationMappingCheckCommonNeon($file))->getMapping();
		}, ApplicationMappingInvalidConfigException::class, $exception);
	}


	public function testGetMapping(): void
	{
		$file = FileMock::create("application:\n mapping:\n  foo: bar\n  waldo: fred", 'neon');
		Assert::same(['foo' => 'bar', 'waldo' => 'fred'], (new ApplicationMappingCheckCommonNeon($file))->getMapping());
	}

}

TestCaseRunner::run(ApplicationMappingCheckCommonNeonTest::class);
