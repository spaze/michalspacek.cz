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


	public function testAddLineNumbersAndWhitespaceMarkers(): void
	{
		Assert::same('<span><span>1 </span>foo<span>&lt;LF&gt;</span></span>' . "\n", $this->strings->addLineNumbersAndWhitespaceMarkers("foo\n")->render());
		$html = '<span class="ln"><span class="nr">1 </span>foo<span class="marker">&lt;LF&gt;</span></span>' . "\n" .
			'<span class="ln"><span class="nr">2 </span>bar<span class="marker">&lt;LF&gt;</span></span>' . "\n" .
			'<span class="ln"><span class="nr">3 </span>baz<span class="marker">&lt;CRLF&gt;</span></span>' . "\r\n" .
			'<span class="ln"><span class="nr">4 </span>waldo<span class="marker">&lt;CRLF&gt;</span></span>' . "\r\n" .
			'<span class="ln"><span class="nr">5 </span>quux</span>';
		Assert::same($html, $this->strings->addLineNumbersAndWhitespaceMarkers("foo\nbar\nbaz\r\nwaldo\r\nquux", 'ln', 'nr', 'marker')->render());
	}


	/**
	 * The page shows the file as the host serves it, whitespace included, the same way the end-of-line marker shows
	 * the line ending: a reader comparing the page with their file has to see what they wrote.
	 */
	public function testAddLineNumbersAndWhitespaceMarkersKeepsTheWhitespace(): void
	{
		$html = '<span class="ln"><span class="nr">1 </span>  Contact: mailto:security@example.com <span class="marker">&lt;LF&gt;</span></span>' . "\n" .
			'<span class="ln"><span class="nr">2 </span><span class="marker">→</span>' . "\t" . 'Expires: 2030-01-01T00:00:00.000Z</span>';
		Assert::same($html, $this->strings->addLineNumbersAndWhitespaceMarkers("  Contact: mailto:security@example.com \n\tExpires: 2030-01-01T00:00:00.000Z", 'ln', 'nr', 'marker')->render());
	}


	public function testAddLineNumbersAndWhitespaceMarkersMarksEveryTab(): void
	{
		$html = '<span class="ln"><span class="nr">1 </span>Contact:<span class="marker">→</span>' . "\t" . '<span class="marker">→</span>' . "\t" . 'https://example.com/<span class="marker">→</span>' . "\t" . '<span class="marker">&lt;LF&gt;</span></span>' . "\n";
		Assert::same($html, $this->strings->addLineNumbersAndWhitespaceMarkers("Contact:\t\thttps://example.com/\t\n", 'ln', 'nr', 'marker')->render());
	}


	/**
	 * Only the line ending the marker names comes off, so a carriage return in front of a line feed, or one ending the
	 * file on its own, stays where the host put it instead of being swallowed as more line ending.
	 */
	public function testAddLineNumbersAndWhitespaceMarkersKeepsAStrayCarriageReturn(): void
	{
		$html = '<span class="ln"><span class="nr">1 </span>foo' . "\r" . '<span class="marker">&lt;CRLF&gt;</span></span>' . "\r\n" .
			'<span class="ln"><span class="nr">2 </span>bar' . "\r" . '</span>';
		Assert::same($html, $this->strings->addLineNumbersAndWhitespaceMarkers("foo\r\r\nbar\r", 'ln', 'nr', 'marker')->render());
	}


	/**
	 * A file the host wrote in something other than UTF-8 still has to appear on the page. `Html` escapes text with
	 * `htmlspecialchars()` and no `ENT_SUBSTITUTE`, which answers an empty string for anything holding such a byte, so
	 * the line would otherwise arrive with its number and its end-of-line marker and nothing between them.
	 */
	public function testAddLineNumbersAndWhitespaceMarkersKeepsALineThatIsNotUtf8(): void
	{
		$latin2 = "Contact: mailto:security@example.com\n# Kontakt: Michal \xA9pa\xE8ek\n";
		$replacement = "\u{FFFD}";
		$html = '<span class="ln"><span class="nr">1 </span>Contact: mailto:security@example.com<span class="marker">&lt;LF&gt;</span></span>' . "\n" .
			'<span class="ln"><span class="nr">2 </span># Kontakt: Michal ' . $replacement . 'pa' . $replacement . 'ek<span class="marker">&lt;LF&gt;</span></span>' . "\n";
		Assert::same($html, $this->strings->addLineNumbersAndWhitespaceMarkers($latin2, 'ln', 'nr', 'marker')->render());
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
