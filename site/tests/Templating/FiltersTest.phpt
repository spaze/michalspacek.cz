<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Html;
use Stringable;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */

class FiltersTest extends TestCase
{

	public function __construct(
		private readonly Filters $filters,
	) {
	}


	public function testFormat(): void
	{
		Assert::same('<em>foo</em> bar 303', $this->filters->format('*foo* %s %d', 'bar', 303)->render());

		$toString = new class () implements Stringable {

			public function __toString(): string
			{
				return __FUNCTION__;
			}

		};
		$toStringNoInterface = new class () {

			public function __toString(): string
			{
				return __FUNCTION__;
			}

		};
		$html = Html::fromText('foo');
		Assert::same('__toString', $this->filters->format($toString)->render());
		Assert::same('<code>__toString</code>', $this->filters->format("`%s`", $toString)->render());
		Assert::same('__toString', $this->filters->format($toStringNoInterface)->render());
		Assert::same('<code>__toString</code>', $this->filters->format("`%s`", $toStringNoInterface)->render());

		Assert::same('foo', $this->filters->format($html)->render());
		Assert::same('<strong>foo</strong>', $this->filters->format("**%s**", $html)->render());
	}

}

TestCaseRunner::run(FiltersTest::class);
