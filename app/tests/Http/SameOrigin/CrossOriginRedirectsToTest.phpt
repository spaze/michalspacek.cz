<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SameOrigin;

use MichalSpacekCz\Presentation\Admin\Sign\SignPresenter;
use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Attributes\Requires;
use Nette\Application\IPresenterFactory;
use Nette\Application\UI\Presenter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use SplFileInfo;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class CrossOriginRedirectsToTest extends TestCase
{

	public function __construct(
		private readonly IPresenterFactory $presenterFactory,
	) {
	}


	public function testEveryUseIsInAPresenterWithTheOverride(): void
	{
		$uses = $this->findAttributeUses();
		Assert::contains(SignPresenter::class . '::actionOut()', array_keys($uses)); // the scan must find at least the known usage
		foreach ($uses as $name => [$method]) {
			Assert::true(is_subclass_of($method->getDeclaringClass()->getName(), BasePresenter::class), "{$name} is outside the Www\\BasePresenter tree, its detectedCsrf() override would never run and a blocked request would loop");
		}
	}


	/**
	 * A destination that requires same origin itself would just move the redirect loop there, so every use
	 * of the attribute must point to an unguarded absolute action. The attribute extends Requires and presets
	 * sameOrigin: true, so the pairing that arms the check cannot be missing and is not tested here.
	 */
	public function testEveryDestinationIsAnUnguardedAbsoluteAction(): void
	{
		$uses = $this->findAttributeUses();
		Assert::contains(SignPresenter::class . '::actionOut()', array_keys($uses)); // the scan must find at least the known usage
		foreach ($uses as $name => [, $attribute]) {
			$destination = $attribute->destination;
			Assert::true(str_starts_with($destination, ':'), "{$name} must use an absolute destination like ':Admin:Homepage:', not '{$destination}'");
			[$presenterName, $action] = $this->splitDestination($destination);
			$targetClass = $this->presenterFactory->getPresenterClass($presenterName); // throws when the presenter doesn't exist
			if (!class_exists($targetClass)) {
				throw new RuntimeException("The {$targetClass} class should exist, getPresenterClass() checks that"); // here just to narrow the type for ReflectionClass
			}
			$target = new ReflectionClass($targetClass);
			Assert::false($this->requiresSameOrigin($target), "{$name} redirects to {$destination} whose presenter requires same origin, the loop would just move there");
			foreach ([Presenter::formatActionMethod($action), Presenter::formatRenderMethod($action)] as $targetMethodName) {
				if (!$target->hasMethod($targetMethodName)) {
					continue;
				}
				$targetMethod = $target->getMethod($targetMethodName);
				// This also catches redirect chains, a #[CrossOriginRedirectsTo] destination is a Requires(sameOrigin: true) by inheritance
				Assert::false($this->requiresSameOrigin($targetMethod), "{$name} redirects to {$destination} which requires same origin, the loop would just move there");
			}
		}
	}


	/**
	 * @return array<string, array{ReflectionMethod, CrossOriginRedirectsTo}> indexed by "Class::method()"
	 */
	private function findAttributeUses(): array
	{
		$uses = [];
		foreach ($this->findPresenterClasses() as $class) {
			foreach (new ReflectionClass($class)->getMethods() as $method) {
				$attributes = $method->getAttributes(CrossOriginRedirectsTo::class);
				if ($attributes === [] || $method->getDeclaringClass()->getName() !== $class) {
					continue;
				}
				$uses["{$class}::{$method->getName()}()"] = [$method, $attributes[0]->newInstance()];
			}
		}
		return $uses;
	}


	/**
	 * @param ReflectionClass<object>|ReflectionMethod $element
	 */
	private function requiresSameOrigin(ReflectionClass|ReflectionMethod $element): bool
	{
		foreach ($element->getAttributes(Requires::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
			if ($attribute->newInstance()->sameOrigin === true) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Splits ':Admin:Homepage:' into the presenter name 'Admin:Homepage' and the action 'default'.
	 *
	 * @return array{string, string}
	 */
	private function splitDestination(string $destination): array
	{
		$parts = explode(':', substr($destination, 1));
		$action = array_pop($parts);
		if ($action === '') {
			$action = 'default'; // Same as in Nette\Application\UI\Presenter::DefaultAction, which is internal
		}
		return [implode(':', $parts), $action];
	}


	/**
	 * @return list<class-string>
	 */
	private function findPresenterClasses(): array
	{
		$dir = realpath(__DIR__ . '/../../../src/Presentation');
		if ($dir === false) {
			throw new RuntimeException('Could not resolve app/src/Presentation/ directory');
		}
		$found = [];
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
			if (!$file instanceof SplFileInfo || !$file->isFile() || !str_ends_with($file->getFilename(), 'Presenter.php')) {
				continue;
			}
			$realPath = realpath($file->getPathname());
			if ($realPath === false) {
				continue;
			}
			$relative = substr($realPath, strlen($dir) + 1, -strlen('.php'));
			$class = 'MichalSpacekCz\\Presentation\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
			if (class_exists($class)) {
				$found[] = $class;
			}
		}
		return $found;
	}

}

TestCaseRunner::run(CrossOriginRedirectsToTest::class);
