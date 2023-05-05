<?php
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Bridges\ApplicationLatte\Template;
use Spaze\NonceGenerator\Nonce;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

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


	/**
	 * @throws \MichalSpacekCz\Templating\Exceptions\WrongTemplateClassException
	 */
	public function testCreateTemplateWrongClassExtendsTemplateOnly(): void
	{
		$class = new class ($this->latteFactory->create()) extends Template {
		};
		$this->templateFactory->createTemplate(class: $class::class);
	}


	/**
	 * @throws \Nette\InvalidArgumentException
	 */
	public function testCreateTemplateWrongClass(): void
	{
		$class = new class () {
		};
		$this->templateFactory->createTemplate(class: $class::class);
	}

}

$runner->run(TemplateFactoryTest::class);
