<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Validators;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\LocaleLinkGeneratorMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Application;
use Nette\Application\UI\InvalidLinkException;
use Nette\Forms\Controls\TextArea;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class FormValidatorRuleTexyTest extends TestCase
{

	private FormValidatorRuleTexy $rule;


	public function __construct(
		private readonly LocaleLinkGeneratorMock $localeLinkGenerator,
		FormValidatorRuleTexyFactory $texyRuleFactory,
		Application $application,
		ApplicationPresenter $applicationPresenter,
	) {
		$applicationPresenter->setLinkCallback($application, null);
		$this->localeLinkGenerator->setAllLinks(['cs_CZ' => 'https://com.example/']);
		$this->rule = $texyRuleFactory->create();
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->localeLinkGenerator->reset();
	}


	public function testGetRule(): void
	{
		$textArea = new TextArea();
		$textArea->value = 'Le Bar "foo":[link:Www:Talks:talk foo-bar]';
		$rule = $this->rule;
		Assert::true($rule->getRule()($textArea));
		Assert::same([], $textArea->getErrors());

		$textArea = new TextArea();
		$textArea->value = 808;
		Assert::true($rule->getRule()($textArea));
		Assert::same([], $textArea->getErrors());
	}


	public function testGetRuleException(): void
	{
		$this->localeLinkGenerator->willThrow(new ShouldNotHappenException('wuh'));
		$textArea = new TextArea();
		$textArea->value = 'Le Bar "foo":[link:Www:Talks:talk foo/bar]';
		$rule = $this->rule;
		Assert::true($rule->getRule()($textArea));
		Assert::same([ShouldNotHappenException::class . ': wuh'], $textArea->getErrors());
	}


	public function testGetRuleInvalidLink(): void
	{
		$this->localeLinkGenerator->willThrow(new InvalidLinkException('oops/bar'));
		$textArea = new TextArea();
		$textArea->value = 'Le Bar "foo":[link:Www:Talks:talk foo/bar]';
		$rule = $this->rule;
		Assert::true($rule->getRule()($textArea));
		Assert::same(['Invalid link: oops/bar'], $textArea->getErrors());
	}


	/**
	 * @return array<string, array{string, string}>
	 */
	public function getDisallowedSchemes(): array
	{
		return [
			'explicit mailto: in bracketed URL slot' => ['Mail me "here":[mailto:foo@example.com]', 'mailto'],
			'explicit tel: in bracketed URL slot' => ['Call "us":[tel:+1234567890]', 'tel'],
			'javascript: in bracketed URL slot' => ['Try "this":[javascript:alert(1)]', 'javascript'],
			'data: URL' => ['"img":[data:text/html,<script>alert(1)</script>]', 'data'],
			'ftp:' => ['"download":[ftp://example.com/file]', 'ftp'],
			'mailto: inline (no brackets)' => ['Mail "here":mailto:foo@example.com', 'mailto'],
			'uppercase JAVASCRIPT:' => ['"x":[JAVASCRIPT:alert(1)]', 'JAVASCRIPT'],
			'ref-def at line start' => ["[ref]: mailto:foo@example.com", 'mailto'],
			'ref-def after newline' => ["Some intro.\n[ref]: javascript:alert(1)", 'javascript'],
			'bare ftp:// after space' => ['Get the file from ftp://example.com today', 'ftp'],
			'bare ftp:// at start' => ['ftp://example.com is the canonical URL', 'ftp'],
			'image source with mailto:' => ['[* mailto:foo *]', 'mailto'],
			'image source with javascript:' => ['[* javascript:alert(1) *]', 'javascript'],
			'image anchor with mailto: (*] close)' => ['[* image.jpg *]:mailto:foo@example.com', 'mailto'],
			'image anchor with tel: (>] close)' => ['[* image.jpg >]:tel:+1234567890', 'tel'],
		];
	}


	/** @dataProvider getDisallowedSchemes */
	public function testGetRuleRejectsDisallowedUrlScheme(string $value, string $scheme): void
	{
		$textArea = new TextArea();
		$textArea->value = $value;
		Assert::true(($this->rule->getRule())($textArea));
		Assert::same(
			["URL scheme '{$scheme}' is not allowed in Texy links; allowed: http, https, link"],
			$textArea->getErrors(),
		);
	}


	/**
	 * @return array<string, array{string}>
	 */
	public function getAllowedTexyContent(): array
	{
		return [
			'http: URL' => ['Visit "us":[http://example.com] today'],
			'https: URL' => ['"docs":[https://example.com/docs]'],
			'bare email (LinkModule auto-mailto)' => ['"mail me":[foo@example.com]'],
			'www. prefix (LinkModule auto-http)' => ['"site":[www.example.com]'],
			'prose mentioning "mailto:" without brackets' => ['The mailto: scheme is older than HTTP'],
			'colon inside label (not URL slot)' => ['"see mailto:foo in code":[https://example.com]'],
			'plain Texy markup' => ['**bold** and //italic//, no links here'],
			'numeric time' => ['Starts at 14:30, ends at 15:00'],
			'ref-def with http' => ["[ref]: http://example.com"],
			'[ref]: not at line start' => ['Inline [ref]: mailto:foo, mid-prose, not a ref-def'],
			'image source, no scheme' => ['[* /i/foo.jpg *]'],
			'image source, https' => ['[* https://example.com/foo.jpg *]'],
			'image anchor, https (*] close)' => ['[* image.jpg *]:https://example.com'],
			'image anchor, https (>] close)' => ['[* image.jpg >]:https://example.com'],
		];
	}


	/** @dataProvider getAllowedTexyContent */
	public function testGetRuleAcceptsAllowedTexyContent(string $value): void
	{
		$textArea = new TextArea();
		$textArea->value = $value;
		Assert::true(($this->rule->getRule())($textArea));
		Assert::same([], $textArea->getErrors());
	}

}

TestCaseRunner::run(FormValidatorRuleTexyTest::class);
