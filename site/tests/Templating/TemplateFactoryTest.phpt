<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use DateTimeImmutable;
use MichalSpacekCz\Templating\Exceptions\WrongTemplateClassException;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\InvalidArgumentException;
use Spaze\NonceGenerator\Nonce;
use Tester\Assert;
use Tester\FileMock;
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
		$file = FileMock::create('{="/foo.png"|staticUrl}, {="/bar.png"|staticImageUrl}, {="**baz**"|format}, {$start|localeDay}, {$start|localeMonth}, {$start|localeIntervalDay:$end}, {$start|localeIntervalMonth:$end}');
		$template = $this->templateFactory->createTemplate();
		$template->start = new DateTimeImmutable('2023-08-23');
		$template->end = new DateTimeImmutable('2023-09-03');
		Assert::type(DefaultTemplate::class, $template);
		$providers = $template->getLatte()->getProviders();
		Assert::hasKey('uiNonce', $providers);
		Assert::same($this->nonce->getValue(), $providers['uiNonce']);
		Assert::same('https://www.domain.example/foo.png, https://www.domain.example/i/images/bar.png, <strong>baz</strong>, 23. srpna 2023, srpen 2023, 23. srpna – 3. září 2023, srpen–září 2023', $template->renderToString($file));
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
