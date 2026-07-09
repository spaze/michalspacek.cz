<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Api\Endpoint;

use MichalSpacekCz\Presentation\Api\BasePresenter;
use MichalSpacekCz\Presentation\Api\Certificates\CertificatesPresenter;
use MichalSpacekCz\Presentation\Api\Company\CompanyPresenter;
use MichalSpacekCz\Test\TestCaseRunner;
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
final class EndpointAccessTest extends TestCase
{

	/**
	 * Every concrete presenter under the Api module is either a gated endpoint (extends the base and declares
	 * exactly one access attribute) or explicitly #[NotAnEndpoint]; a mis-based or undeclared one fails here.
	 */
	public function testEveryApiPresenterIsGatedOrMarkedNotAnEndpoint(): void
	{
		$gated = [];
		foreach ($this->findApiPresenterClasses() as $class) {
			$reflection = new ReflectionClass($class);
			if ($reflection->isAbstract()) {
				continue; // the Api base presenter itself
			}
			if (is_subclass_of($class, BasePresenter::class)) {
				$gated[] = $class;
				Assert::count(1, $reflection->getAttributes(EndpointAccessAttribute::class, ReflectionAttribute::IS_INSTANCEOF), "{$class} must declare exactly one access attribute");
			} else {
				Assert::count(1, $reflection->getAttributes(NotAnEndpoint::class), "{$class} is under Presentation/Api but does not extend its BasePresenter, so the access gate never runs for it; mark it #[NotAnEndpoint] or extend the base");
			}
		}
		Assert::contains(CompanyPresenter::class, $gated);
		Assert::contains(CertificatesPresenter::class, $gated);
	}


	public function testIsDeclaredIsTrueForExactlyOneAccessAttribute(): void
	{
		$one = new #[EndpointAllowsPublicAccess] class extends BasePresenter {
		};
		$two = new #[EndpointAllowsPublicAccess] #[EndpointRequiresAuthentication] class extends BasePresenter {
		};
		$none = new class extends BasePresenter {
		};
		Assert::true(EndpointAccess::isDeclared($one)); // exactly one access attribute
		Assert::false(EndpointAccess::isDeclared($two)); // two -> not exactly one
		Assert::false(EndpointAccess::isDeclared($none)); // none declared
	}


	public function testBaseStartupIsFinalSoTheAccessCheckCannotBeSkipped(): void
	{
		Assert::true(new ReflectionMethod(BasePresenter::class, 'startup')->isFinal());
	}


	/**
	 * @return list<class-string>
	 */
	private function findApiPresenterClasses(): array
	{
		$apiDir = realpath(__DIR__ . '/../../../src/Presentation/Api');
		if ($apiDir === false) {
			throw new RuntimeException('Could not resolve app/src/Presentation/Api/ directory');
		}
		$found = [];
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($apiDir)) as $file) {
			if (!$file instanceof SplFileInfo || !$file->isFile() || !str_ends_with($file->getFilename(), 'Presenter.php')) {
				continue;
			}
			$realPath = realpath($file->getPathname());
			if ($realPath === false) {
				continue;
			}
			$relative = substr($realPath, strlen($apiDir) + 1, -strlen('.php'));
			$class = 'MichalSpacekCz\\Presentation\\Api\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
			if (class_exists($class)) {
				$found[] = $class;
			}
		}
		return $found;
	}

}

TestCaseRunner::run(EndpointAccessTest::class);
