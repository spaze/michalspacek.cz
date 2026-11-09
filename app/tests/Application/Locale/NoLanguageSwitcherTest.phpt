<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Locale;

use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\Presentation\Www\Trainings\TrainingsPresenter;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\DI\Container;
use ReflectionClass;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class NoLanguageSwitcherTest extends TestCase
{

	public function __construct(
		private readonly Container $container,
	) {
	}


	public function testEveryReasonIsGiven(): void
	{
		$found = $missing = [];
		foreach ($this->container->findByType(BasePresenter::class) as $name) {
			$class = $this->container->getServiceType($name);
			foreach ((new ReflectionClass($class))->getMethods() as $method) {
				foreach ($method->getAttributes(NoLanguageSwitcher::class) as $attribute) {
					$found[] = "{$class}::{$method->getName()}()";
					if (trim($attribute->newInstance()->reason) === '') {
						$missing[] = "{$class}::{$method->getName()}()";
					}
				}
			}
		}
		Assert::contains(TrainingsPresenter::class . '::actionFiles()', $found);
		Assert::same([], $missing, 'Say why the page has no language switcher');
	}

}

TestCaseRunner::run(NoLanguageSwitcherTest::class);
