<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use LogicException;
use MichalSpacekCz\Application\Bootstrap;
use Nette\Utils\Type;
use ReflectionException;
use ReflectionMethod;
use Tester\Environment;
use Tester\TestCase;

final class TestCaseRunner
{

	private const string INCLUDE_SKIPPED_ENV_VAR_NAME = 'TEST_CASE_RUNNER_INCLUDE_SKIPPED';
	private const string INCLUDE_SKIPPED_ENV_VAR_VALUE = '1';


	/**
	 * @param class-string $test
	 * @return void
	 */
	public static function run(string $test): void
	{
		$params = [];
		try {
			$method = new ReflectionMethod($test, '__construct');
			$container = Bootstrap::bootTest();
			Environment::setup();
			foreach ($method->getParameters() as $parameter) {
				$type = Type::fromReflection($parameter);
				$paramIdent = "Parameter #{$parameter->getPosition()} \${$parameter->getName()}";
				if ($type === null) {
					throw new LogicException("{$paramIdent} has no type specified in {$test}::__construct()");
				}
				if ($type->isUnion()) {
					throw new LogicException("{$paramIdent} specifies a union type {$type} but only a simple type is supported");
				}
				if ($type->isIntersection()) {
					throw new LogicException("{$paramIdent} specifies an intersection type {$type} but only a simple type is supported");
				}
				$singleName = $type->getSingleName();
				if ($singleName === null) {
					throw new LogicException("{$paramIdent} specifies a non-simple type");
				}
				if (!class_exists($singleName) && !interface_exists($singleName)) {
					throw new LogicException("{$paramIdent} specifies a type {$type} but the class or interface doesn't exist");
				}
				$params[] = $container->getByType($singleName);
			}
		} catch (ReflectionException) {
			// pass, __construct() does not exist
		}
		$testCase = new $test(...$params);
		if (!$testCase instanceof TestCase) {
			throw new LogicException(sprintf("%s() can only be used to run tests that extend %s", __METHOD__, TestCase::class));
		}
		$testCase->run();
	}


	public static function needsInternet(): void
	{
		if (getenv(self::INCLUDE_SKIPPED_ENV_VAR_NAME) === self::INCLUDE_SKIPPED_ENV_VAR_VALUE) {
			return;
		}
		Environment::skip(sprintf(
			'The test uses the Internet, to not skip the test case run it with `%s=%s`',
			TestCaseRunner::INCLUDE_SKIPPED_ENV_VAR_NAME,
			TestCaseRunner::INCLUDE_SKIPPED_ENV_VAR_VALUE,
		));
	}

}
