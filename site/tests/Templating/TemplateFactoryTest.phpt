<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use MichalSpacekCz\Templating\Exceptions\WrongTemplateClassException;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\InvalidArgumentException;
use Spaze\NonceGenerator\Nonce;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TemplateFactoryTest extends TestCase
{

	public function __construct(
		private readonly Nonce $nonce,
		private readonly LatteFactory $latteFactory,
		private readonly TemplateFactory $templateFactory,
	) {
	}


	public function testCreateTemplate(): void
	{
		$template = $this->templateFactory->createTemplate();
		Assert::type(DefaultTemplate::class, $template);
		$providers = $template->getLatte()->getProviders();
		Assert::hasKey('uiNonce', $providers);
		Assert::same($this->nonce->getValue(), $providers['uiNonce']);
	}


	public function testCreateTemplateWrongClassExtendsTemplateOnly(): void
	{
		$class = new class ($this->latteFactory->create()) extends Template {
		};
		Assert::exception(function () use ($class): void {
			$this->templateFactory->createTemplate(class: $class::class);
		}, WrongTemplateClassException::class);
	}


	public function testCreateTemplateWrongClass(): void
	{
		$class = new class () {
		};
		Assert::exception(function () use ($class): void {
			$this->templateFactory->createTemplate(class: $class::class);
		}, InvalidArgumentException::class);
	}

}

TestCaseRunner::run(TemplateFactoryTest::class);
