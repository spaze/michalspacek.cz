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
final class FormValidatorTexyRuleTest extends TestCase
{

	private FormValidatorTexyRule $rule;


	public function __construct(
		private readonly LocaleLinkGeneratorMock $localeLinkGenerator,
		FormValidatorTexyRuleFactory $texyRuleFactory,
		Application $application,
		ApplicationPresenter $applicationPresenter,
	) {
		$applicationPresenter->setLinkCallback($application, fn() => 'https://example.com');
		$this->localeLinkGenerator->setAllLinks(['cs_CZ' => 'https://com.example/']);
		$this->rule = $texyRuleFactory->create();
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->localeLinkGenerator->reset();
	}


	public function testGetRuleException(): void
	{
		$this->localeLinkGenerator->willThrow(new ShouldNotHappenException('wuh'));
		$textArea = new TextArea();
		$textArea->value = 'Le Bar "foo":[link:Www:Talks:talk foo/bar]';
		$rule = $this->rule;
		Assert::false($rule->getRule()($textArea));
		Assert::same(ShouldNotHappenException::class . ': wuh', (string)$rule);
	}


	public function testGetRuleInvalidLink(): void
	{
		$this->localeLinkGenerator->willThrow(new InvalidLinkException('oops/bar'));
		$textArea = new TextArea();
		$textArea->value = 'Le Bar "foo":[link:Www:Talks:talk foo/bar]';
		$rule = $this->rule;
		Assert::false($rule->getRule()($textArea));
		Assert::same('Invalid link: oops/bar', (string)$rule);
	}


	/**
	 * @return array<string, array{0:int|string, 1:string|null}>
	 */
	public function getEndsWith(): array
	{
		return [
			'number' => [303, null],
			'text' => ['string', null],
			'para' => ["Foo\n- Bar\n- Baz\nFred", null],
			'inline tag' => ['Hello **world**', null],
			'link' => ['See "foo":[https://example.com]', null],
			'code' => ['Call `this`', null],
			'empty' => ['', null],
			'whitespace' => ["   \n\t", null],
			'ordered list' => ["Foo\n1. Bar\n2. Baz", 'OL'],
			'unordered list' => ["Foo\n- Bar\n-Baz", 'UL'],
			'blockquote' => ["Foo\n> Bar\n> Baz", 'BLOCKQUOTE'],
			'code block' => ["Foo\n/--\ncode\n\--", 'PRE'],
			'table' => ["| Foo | Bar |\n|-----|-----|\n| Baz | Qux |", 'TABLE'],
		];
	}


	/**
	 * @dataProvider getEndsWith
	 */
	public function testGetRule(string|int $input, ?string $endTag): void
	{
		$textArea = new TextArea();
		$textArea->value = $input;
		$rule = $this->rule;
		if ($endTag !== null) {
			Assert::false($rule->getRule()($textArea));
			Assert::same("Text ends with $endTag, but it should end with a paragraph, otherwise the slide number will be on a separate line", (string)$rule);
		} else {
			Assert::true($rule->getRule()($textArea));
			Assert::same('', (string)$rule);
		}
	}

}

TestCaseRunner::run(FormValidatorTexyRuleTest::class);
