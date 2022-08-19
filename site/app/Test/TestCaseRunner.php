<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use LogicException;
use Nette\DI\Container;
use Nette\Utils\Reflection;
use ReflectionException;
use ReflectionMethod;
use Tester\TestCase;

class TestCaseRunner
{

	public function __construct(
		private readonly Container $container,
	) {
	}


	/**
	 * @param class-string<TestCase> $test
	 * @return void
	 */
	public function run(string $test): void
	{
		$params = [];
		try {
			$method = new ReflectionMethod($test, '__construct');
			foreach ($method->getParameters() as $parameter) {
				$type = Reflection::getParameterType($parameter);
				if ($type === null) {
					throw new LogicException("Parameter #{$parameter->getPosition()} \${$parameter->getName()} has no type specified in {$test}::__construct()");
				}
				if (!class_exists($type) && !interface_exists($type)) {
					throw new LogicException("Parameter #{$parameter->getPosition()} \${$parameter->getName()} specifies a type {$type} but the class or interface doesn't exist");
				}
				$params[] = $this->container->getByType($type);
			}
		} catch (ReflectionException) {
			// pass, __construct() does not exist
		}
		(new $test(...$params))->run();
	}

}
