<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\Placeholders;

use Contributte\Translation\Translator;
use MichalSpacekCz\Http\FetchMetadata\FetchMetadataHeader;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class FetchMetadataTexyFormatterPlaceholderTest extends TestCase
{

	public function __construct(
		private readonly Request $httpRequest,
		private readonly Translator $translator,
		private readonly FetchMetadataTexyFormatterPlaceholder $placeholder,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->httpRequest->reset();
	}


	public function testReplaceEscapesHeaderValueSoMarkupCannotSurvive(): void
	{
		$this->httpRequest->setHeader(FetchMetadataHeader::Dest->value, '<img src=x onerror=alert(1)>');
		Assert::same(
			'Sec-Fetch-Dest: &lt;img src=x onerror=alert(1)&gt;',
			$this->placeholder->replace(FetchMetadataHeader::Dest->value),
		);
	}


	public function testReplaceKeepsNotSentMarkerAsHtmlWhenHeaderAbsent(): void
	{
		// A header the browser did not send renders as a real <em> "[not sent]" marker, not escaped literal text.
		$notSent = $this->translator->translate('messages.httpHeaders.headerNotSent');
		Assert::same(
			"Sec-Fetch-Site: <em>[{$notSent}]</em>",
			$this->placeholder->replace(FetchMetadataHeader::Site->value),
		);
	}

}

TestCaseRunner::run(FetchMetadataTexyFormatterPlaceholderTest::class);
