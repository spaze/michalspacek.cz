<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Application\Exceptions\ParameterNotStringException;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\UI\Component;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class ComponentParametersTest extends TestCase
{

	private Component $component;


	public function __construct(
		private readonly ComponentParameters $componentParameters,
	) {
		$this->component = new class extends Component {
		};
	}


	public function testGetStringParameters(): void
	{
		$this->component->loadState([]);
		Assert::same([], $this->componentParameters->getStringParameters($this->component));

		$this->component->loadState(['foo' => 'bar', 'baz' => 'quux', 1 => 'one']);
		Assert::same(['foo' => 'bar', 'baz' => 'quux', '1' => 'one'], $this->componentParameters->getStringParameters($this->component));

		$this->component->loadState(['foo' => 'bar', 'number' => 1, 'baz' => 'quux']);
		Assert::exception(function (): void {
			$this->componentParameters->getStringParameters($this->component);
		}, ParameterNotStringException::class, "Component parameter 'number' is not a string but it's a int");
	}

}

TestCaseRunner::run(ComponentParametersTest::class);
