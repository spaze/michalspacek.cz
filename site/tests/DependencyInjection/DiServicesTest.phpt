<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DependencyInjection;

use MichalSpacekCz\DependencyInjection\Exceptions\DiServicesConfigInvalidException;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\FileMock;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class DiServicesTest extends TestCase
{

	public function testGetAllServices(): void
	{
		Assert::noError(function (): void {
			DiServices::getAllClasses();
		});
	}


	/**
	 * @return list<array{0:string, 1:string|null}>
	 */
	public function getConfigFile(): array
	{
		return [
			[
				'foo',
				': not an array',
			],
			[
				"foo:",
				":bar: section doesn't exist",
			],
			[
				"bar:\n\tfred",
				":bar: section not iterable",
			],
			[
				"bar:\n\tfred: 3.14",
				null,
			],
			[
				"bar:\n\tfred: @DateTime",
				null,
			],
			[
				"bar:\n\tboom: shakalaka",
				":bar: class or interface 'shakalaka' doesn't exist",
			],
			[
				"bar:\n\tfred:\n\t\t- 3.14",
				":bar: Unsupported array '[3.14]'",
			],
		];
	}


	/** @dataProvider getConfigFile */
	public function testGetAllServicesNotClassStrings(string $config, ?string $exceptionMessage): void
	{
		$configFile = FileMock::create($config, 'neon');
		$diServices = new class ([$configFile => 'bar']) extends DiServices {

			/**
			 * @param array<string, non-empty-lowercase-string> $configFiles
			 */
			public function __construct(array $configFiles)
			{
				self::$configFiles = $configFiles;
			}

		};
		if ($exceptionMessage === null) {
			Assert::noError(function () use ($diServices): void {
				$diServices::getAllClasses();
			});
		} else {
			Assert::exception(function () use ($diServices): void {
				$diServices::getAllClasses();
			}, DiServicesConfigInvalidException::class, "{$configFile}{$exceptionMessage}");
		}
	}

}

TestCaseRunner::run(DiServicesTest::class);
