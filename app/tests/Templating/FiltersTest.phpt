<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Html;
use Override;
use Stringable;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */

final class FiltersTest extends TestCase
{

	public function __construct(
		private readonly Filters $filters,
	) {
	}


	public function testFormat(): void
	{
		Assert::same('&lt;b&gt;bold&lt;/b&gt;', $this->filters->format('%s', '<b>bold</b>')->render());
		Assert::same('**bold**', $this->filters->format('%s', '**bold**')->render());
		Assert::same('<em>foo</em>', $this->filters->format('//%s//', 'foo')->render());
	}


	public function testFormatPossiblyUnsafeHtml(): void
	{
		Assert::same('<em>foo</em> bar 303', $this->filters->formatPossiblyUnsafeHtml('*foo* %s %d', 'bar', 303)->render());
		Assert::same('<strong>foo</strong>', $this->filters->formatPossiblyUnsafeHtml('**%s**', 'foo')->render());

		$toString = new class () implements Stringable {

			#[Override]
			public function __toString(): string
			{
				return __FUNCTION__;
			}

		};
		$toStringNoInterface = new class () {

			#[Override]
			public function __toString(): string
			{
				return __FUNCTION__;
			}

		};
		$html = Html::fromText('foo');
		Assert::same('__toString', $this->filters->formatPossiblyUnsafeHtml($toString)->render());
		Assert::same('<code>__toString</code>', $this->filters->formatPossiblyUnsafeHtml('`%s`', $toString)->render());
		Assert::same('__toString', $this->filters->formatPossiblyUnsafeHtml($toStringNoInterface)->render());
		Assert::same('<code>__toString</code>', $this->filters->formatPossiblyUnsafeHtml('`%s`', $toStringNoInterface)->render());

		Assert::same('foo', $this->filters->formatPossiblyUnsafeHtml($html)->render());
		Assert::same('<strong>foo</strong>', $this->filters->formatPossiblyUnsafeHtml('**%s**', $html)->render());
	}


	public function testTruncateMiddle(): void
	{
		Assert::same(
			'<span class="truncateMiddle" title="abcdefghij"><span>abcdef</span><span>ghij</span></span>',
			$this->filters->truncateMiddle('abcdefghij', 4)->render(),
		);
		Assert::same(
			'<span class="truncateMiddle" title="abc"><span></span><span>abc</span></span>',
			$this->filters->truncateMiddle('abc', 4)->render(),
		);
		Assert::same(
			'<span class="truncateMiddle" title="the full value"><span>abcdef</span><span>ghij</span></span>',
			$this->filters->truncateMiddle('abcdefghij', 4, 'the full value')->render(),
		);
		Assert::contains('&lt;', $this->filters->truncateMiddle('a<b', 1)->render());
	}

}

TestCaseRunner::run(FiltersTest::class);
