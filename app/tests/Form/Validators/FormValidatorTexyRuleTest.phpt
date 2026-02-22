<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Validators;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Formatter\TexyPhraseHandler;
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
use Texy\Texy;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class FormValidatorTexyRuleTest extends TestCase
{

	public function __construct(
		private readonly LocaleLinkGeneratorMock $localeLinkGenerator,
		private readonly TexyFormatter $texyFormatter,
		private readonly TexyPhraseHandler $phraseHandler,
		Application $application,
		ApplicationPresenter $applicationPresenter,
	) {
		$applicationPresenter->setLinkCallback($application, fn() => 'https://example.com');
		$this->localeLinkGenerator->setAllLinks(['cs_CZ' => 'https://com.example/']);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->localeLinkGenerator->reset();
	}


	public function testGetRule(): void
	{
		$rule = $this->getRule()->getRule();

		$textArea = new TextArea();
		$textArea->value = 303;
		Assert::true($rule($textArea));

		$textArea = new TextArea();
		$textArea->value = 'string';
		Assert::true($rule($textArea));
	}


	public function testGetRuleException(): void
	{
		$this->localeLinkGenerator->willThrow(new ShouldNotHappenException('wuh'));
		$textArea = new TextArea();
		$textArea->value = 'Le Bar "foo":[link:Www:Talks:talk foo/bar]';
		$rule = $this->getRule();
		Assert::false($rule->getRule()($textArea));
		Assert::same(ShouldNotHappenException::class . ': wuh', (string)$rule);
	}


	public function testGetRuleInvalidLink(): void
	{
		$this->localeLinkGenerator->willThrow(new InvalidLinkException('oops/bar'));
		$textArea = new TextArea();
		$textArea->value = 'Le Bar "foo":[link:Www:Talks:talk foo/bar]';
		$rule = $this->getRule();
		Assert::false($rule->getRule()($textArea));
		Assert::same('Invalid link: oops/bar', (string)$rule);
	}


	protected function getRule(): FormValidatorTexyRule
	{
		$texy = new Texy();
		$texy->addHandler('phrase', $this->phraseHandler->solve(...));
		return new FormValidatorTexyRule($this->texyFormatter->withTexy($texy));
	}

}

TestCaseRunner::run(FormValidatorTexyRuleTest::class);
