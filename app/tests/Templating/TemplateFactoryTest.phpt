<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use DateTimeImmutable;
use MichalSpacekCz\Test\TestCaseRunner;
use Spaze\NonceGenerator\Nonce;
use Tester\Assert;
use Tester\FileMock;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class TemplateFactoryTest extends TestCase
{

	public function __construct(
		private readonly Nonce $nonce,
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

}

TestCaseRunner::run(TemplateFactoryTest::class);
