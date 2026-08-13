<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DependencyInjection;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\IPresenter;
use Nette\DI\Container;
use ReflectionClass;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/**
 * Presenters are services because config/presenters.neon lists them one by one, and a presenter left out of that
 * list still works: the presenter factory builds whatever the router asked for, service or not. So the omission
 * is invisible until something wants the definition, dependencies resolved at compile time or a presenter
 * discovered from the Composer classmap, and then it shows up as a difference between machines rather than as
 * a failure. This walks src/ instead and asks the container about every presenter it finds.
 *
 * @testCase
 */
final class PresentersRegisteredTest extends TestCase
{

	public function __construct(
		private readonly Container $container,
	) {
	}


	public function testEveryPresenterInTheSourceTreeIsAService(): void
	{
		$registered = [];
		foreach ($this->container->findByType(IPresenter::class) as $name) {
			$type = $this->container->getServiceType($name);
			$registered[$type] = true;
		}

		$missing = [];
		foreach ($this->presenterClasses() as $class) {
			if (!isset($registered[$class])) {
				$missing[] = $class;
			}
		}
		Assert::same([], $missing, 'these presenters are not services, add them to config/presenters.neon');
	}


	/**
	 * Every concrete presenter in src/, found by the file name and turned into a class name by the path, which
	 * phpcs keeps in step with the namespace
	 *
	 * @return list<string>
	 */
	private function presenterClasses(): array
	{
		$srcDir = __DIR__ . '/../../src';
		$files = glob($srcDir . '/{,*/,*/*/,*/*/*/,*/*/*/*/}*Presenter.php', GLOB_BRACE);
		Assert::type('array', $files);
		assert(is_array($files));
		Assert::true(count($files) > 40, 'found almost no presenter files, the search is broken rather than the config');

		$classes = [];
		foreach ($files as $file) {
			$relative = substr($file, strlen($srcDir) + 1, -strlen('.php'));
			$class = 'MichalSpacekCz\\' . str_replace('/', '\\', $relative);
			Assert::true(class_exists($class), "{$class} doesn't exist, the class name is built from the path so the two have drifted apart");
			assert(class_exists($class)); // Assert::true() doesn't narrow the type for phpstan and psalm
			if (!(new ReflectionClass($class))->isAbstract() && is_subclass_of($class, IPresenter::class)) {
				$classes[] = $class;
			}
		}
		return $classes;
	}

}

TestCaseRunner::run(PresentersRegisteredTest::class);
