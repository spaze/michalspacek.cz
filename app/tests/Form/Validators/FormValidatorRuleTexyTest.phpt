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
		$applicationPresenter->setLinkCallback($application, fn() => 'https://example.com');
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
		Assert::same('', (string)$rule);
		Assert::same((string)$rule, (string)$rule->getMessage());

		$textArea = new TextArea();
		$textArea->value = 808;
		Assert::true($rule->getRule()($textArea));
		Assert::same('', (string)$rule);
		Assert::same((string)$rule, (string)$rule->getMessage());
	}


	public function testGetRuleException(): void
	{
		$this->localeLinkGenerator->willThrow(new ShouldNotHappenException('wuh'));
		$textArea = new TextArea();
		$textArea->value = 'Le Bar "foo":[link:Www:Talks:talk foo/bar]';
		$rule = $this->rule;
		Assert::false($rule->getRule()($textArea));
		Assert::same(ShouldNotHappenException::class . ': wuh', (string)$rule);
		Assert::same((string)$rule, (string)$rule->getMessage());
	}


	public function testGetRuleInvalidLink(): void
	{
		$this->localeLinkGenerator->willThrow(new InvalidLinkException('oops/bar'));
		$textArea = new TextArea();
		$textArea->value = 'Le Bar "foo":[link:Www:Talks:talk foo/bar]';
		$rule = $this->rule;
		Assert::false($rule->getRule()($textArea));
		Assert::same('Invalid link: oops/bar', (string)$rule);
		Assert::same((string)$rule, (string)$rule->getMessage());
	}

}

TestCaseRunner::run(FormValidatorRuleTexyTest::class);
