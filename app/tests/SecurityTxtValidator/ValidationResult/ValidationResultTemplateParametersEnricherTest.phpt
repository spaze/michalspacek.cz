<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\ValidationResult;

use MichalSpacekCz\SecurityTxtValidator\Issue\SecurityTxtLineIssue;
use MichalSpacekCz\Test\TestCaseRunner;
use Spaze\SecurityTxt\Parser\SecurityTxtParser;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class ValidationResultTemplateParametersEnricherTest extends TestCase
{

	public function __construct(
		private readonly ValidationResultTemplateParametersEnricher $enricher,
		private readonly SecurityTxtParser $parser,
	) {
	}


	/**
	 * The line is shown in the issue heading, outside `<pre>`, and the issue can be about the very whitespace that collapses there.
	 * Line 1 has a warning and line 2 an error, and every issue on a line shares its heading however many the library reports.
	 */
	public function testLineIssueKeepsTheWhitespaceOfItsLine(): void
	{
		$contents = "  X-Foo:\tbar  \n  Contact:\tfoo  \n";
		$template = new ValidationResultTemplateParameters();
		$this->enricher->addFromParseStringResult($template, $this->parser->parseString($contents), $contents);
		$headings = [];
		foreach ($template->lineIssues as $line => $issues) {
			$headings[$line] = array_values(array_unique(array_map(fn(SecurityTxtLineIssue $issue): string => $issue->getLineContents()->render(), $issues)));
		}
		Assert::same([
			1 => ["\u{A0}\u{A0}X-Foo:<span class=\"marker\">→</span>bar"],
			2 => ["\u{A0}\u{A0}Contact:<span class=\"marker\">→</span>foo"],
		], $headings);
		Assert::contains('<span class="marker">→</span>' . "\t" . 'bar', $template->contents?->render() ?? '');
	}

}

TestCaseRunner::run(ValidationResultTemplateParametersEnricherTest::class);
