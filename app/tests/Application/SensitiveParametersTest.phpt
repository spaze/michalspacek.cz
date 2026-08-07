<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Test\TestCaseRunner;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;
use SensitiveParameter;
use SplFileInfo;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/*
 * Tracy writes every stack frame's arguments into the exception log, so a value passed as a plain string ends up
 * there whenever something deeper throws. ExceptionLogSecretsTest proves `#[SensitiveParameter]` hides it; this one
 * makes sure the attribute is on every parameter that carries one, including ones added later.
 *
 * The list is deliberately short. A name has to mean personal data wherever it appears, or the failures become
 * noise and get waved through: `$name` alone is 98 parameters in src/, nearly all of them header names, session
 * keys and talk titles. Widen it when a name earns its place, and add an allow-list here if one ever needs an
 * exception, with the reason written down.
 *
 * Strings only. An object holding the same data leaks it too, `TrainingControlsAttendee` takes the form's
 * `TextInput $email` and Tracy would dump its value, but `#[SensitiveParameter]` is meant for scalars and objects
 * need `keysToHide` or `__debugInfo()` instead. Different fix, so not this test's business.
 */

/** @testCase */
final class SensitiveParametersTest extends TestCase
{

	private const array PERSONAL_DATA_PARAMETERS = [
		'email',
	];


	public function testPersonalDataParametersAreMarkedSensitive(): void
	{
		$srcDir = realpath(__DIR__ . '/../../src');
		if ($srcDir === false) {
			throw new RuntimeException('Could not resolve app/src/ directory');
		}

		$unmarked = [];
		$found = 0;
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir)) as $file) {
			if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}
			$class = 'MichalSpacekCz\\' . str_replace(['/', '.php'], ['\\', ''], substr($file->getPathname(), strlen($srcDir) + 1));
			if (!class_exists($class) && !interface_exists($class) && !trait_exists($class) && !enum_exists($class)) {
				continue;
			}
			$reflection = new ReflectionClass($class);
			foreach ($reflection->getMethods() as $method) {
				if ($method->getDeclaringClass()->getName() !== $class) { // inherited ones belong to whoever declares them
					continue;
				}
				foreach ($method->getParameters() as $parameter) {
					$type = $parameter->getType();
					if (
						!in_array($parameter->getName(), self::PERSONAL_DATA_PARAMETERS, true)
						|| !$type instanceof ReflectionNamedType
						|| $type->getName() !== 'string'
					) {
						continue;
					}
					$found++;
					if ($parameter->getAttributes(SensitiveParameter::class) === []) {
						$unmarked[] = sprintf('%s::%s($%s)', $class, $method->getName(), $parameter->getName());
					}
				}
			}
		}

		sort($unmarked);
		Assert::same([], $unmarked, 'Add #[SensitiveParameter] to these, or they end up in the exception log in full');
		Assert::true($found > 0, 'Found no parameters to check at all, so this test is passing without checking anything');
	}

}

TestCaseRunner::run(SensitiveParametersTest::class);
