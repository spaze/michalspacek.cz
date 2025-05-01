<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

use MichalSpacekCz\Test\NoOpTranslator;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Utils\Strings;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class StringsTest extends TestCase
{

	public function __construct(
		private readonly Strings $strings,
		private readonly NoOpTranslator $translator,
	) {
	}


	/**
	 * @return list<array{0:string, 1:string, 2:string}>
	 */
	public function getInitials(): array
	{
		return [
			['Chrome', 'C', 'Ch'],
			['chrome', 'C', 'Ch'],
			['Firefox', 'F', 'F'],
			['firefox', 'F', 'F'],
			['Ch', 'C', 'Ch'],
			['CH', 'C', 'Ch'],
			['cH', 'C', 'Ch'],
			['C', 'C', 'C'],
			['c', 'C', 'C'],
			['', '', ''],
			[' ', ' ', ' '],
			['-', '-', '-'],
			['-Foo', '-', '-'],
			[' Chrome', ' ', ' '],
			['🍦', '🍦', '🍦'],
			['🧊 cream', '🧊', '🧊'],
		];
	}


	/**
	 * @dataProvider getInitials
	 */
	public function testGetInitialLetterUppercase(string $string, string $enInitial, string $csInitial): void
	{
		$this->translator->setDefaultLocale('en_US');
		Assert::same($enInitial, $this->strings->getInitialLetterUppercase($string));

		$this->translator->setDefaultLocale('cs_CZ');
		Assert::same($csInitial, $this->strings->getInitialLetterUppercase($string));
	}


	public function testLength(): void
	{
		Assert::same(9, $this->strings->length('ěščřžýáíé'));
	}


	public function testAddLineNumbersAndEolChars(): void
	{
		Assert::same('<span><span>1 </span>foo<span>&lt;LF&gt;</span></span>' . "\n", $this->strings->addLineNumbersAndEolChars("foo\n")->render());
		$html = '<span class="ln"><span class="nr">1 </span>foo<span class="eol">&lt;LF&gt;</span></span>' . "\n" .
			'<span class="ln"><span class="nr">2 </span>bar<span class="eol">&lt;LF&gt;</span></span>' . "\n" .
			'<span class="ln"><span class="nr">3 </span>baz<span class="eol">&lt;CRLF&gt;</span></span>' . "\r\n" .
			'<span class="ln"><span class="nr">4 </span>waldo<span class="eol">&lt;CRLF&gt;</span></span>' . "\r\n" .
			'<span class="ln"><span class="nr">5 </span>quux</span>';
		Assert::same($html, $this->strings->addLineNumbersAndEolChars("foo\nbar\nbaz\r\nwaldo\r\nquux", 'ln', 'nr', 'eol')->render());
	}


	/**
	 * @return array<array{0:string, 1:string}>
	 */
	public function getWordBreaks(): array
	{
		return [
			['', ''],
			['foo', 'foo'],
			['.foo', '.foo'],
			['foo.', 'foo.'],
			['.foo.', '.foo.'],
			['...foo...', '...foo<wbr>...'],
			['foo...bar', 'foo<wbr>...bar'],
			['https://foo.bar.example/.well-unknown/hax.txt', 'https://foo<wbr>.bar<wbr>.example/<wbr>.well-unknown/hax<wbr>.txt'],
			['https://foo.bar.example/.well..unknown/hax.txt', 'https://foo<wbr>.bar<wbr>.example/<wbr>.well<wbr>..unknown/hax<wbr>.txt'],
		];
	}


	/**
	 * @dataProvider getWordBreaks
	 */
	public function testAddWordBreaks(string $string, string $expected): void
	{
		Assert::same($expected, $this->strings->addWordBreaks($string)->render());
	}

}

TestCaseRunner::run(StringsTest::class);
