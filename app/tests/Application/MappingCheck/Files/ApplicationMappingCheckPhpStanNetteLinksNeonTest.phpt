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
class ApplicationMappingCheckPhpStanNetteLinksNeonTest extends TestCase
{

	public function testFileNotFound(): void
	{
		Assert::exception(function (): void {
			new ApplicationMappingCheckPhpStanNetteLinksNeon('./foo');
		}, ApplicationMappingFileNotFoundException::class, "Application mapping file not found: './foo'");
	}


	public function testIsPrimaryFile(): void
	{
		$file = FileMock::create();
		Assert::false((new ApplicationMappingCheckPhpStanNetteLinksNeon($file))->isPrimaryFile());
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
				"Application mapping config invalid in 'mock://3.neon': Missing 'parameters' key",
			],
			[
				'parameters: foo',
				"Application mapping config invalid in 'mock://4.neon': The 'parameters' key should be an array, but it's string",
			],
			[
				"parameters:\n - foo",
				"Application mapping config invalid in 'mock://5.neon': Missing 'parameters.netteLinks' key",
			],
			[
				"parameters:\n netteLinks: foo",
				"Application mapping config invalid in 'mock://6.neon': The 'parameters.netteLinks' key should be an array, but it's string",
			],
			[
				"parameters:\n netteLinks:\n  - foo",
				"Application mapping config invalid in 'mock://7.neon': Missing 'parameters.netteLinks.applicationMapping' key",
			],
			[
				"parameters:\n netteLinks:\n  applicationMapping: foo",
				"Application mapping config invalid in 'mock://8.neon': The 'parameters.netteLinks.applicationMapping' key should be an array, but it's string",
			],
		];
	}


	/** @dataProvider getConfig */
	public function testGetMappingInvalidConfig(string $config, string $exception): void
	{
		$file = FileMock::create($config, 'neon');
		Assert::exception(function () use ($file): void {
			(new ApplicationMappingCheckPhpStanNetteLinksNeon($file))->getMapping();
		}, ApplicationMappingInvalidConfigException::class, $exception);
	}


	public function testGetMapping(): void
	{
		$file = FileMock::create("parameters:\n netteLinks:\n  applicationMapping:\n   foo: bar\n   waldo: fred", 'neon');
		Assert::same(['foo' => 'bar', 'waldo' => 'fred'], (new ApplicationMappingCheckPhpStanNetteLinksNeon($file))->getMapping());
	}

}

TestCaseRunner::run(ApplicationMappingCheckPhpStanNetteLinksNeonTest::class);
