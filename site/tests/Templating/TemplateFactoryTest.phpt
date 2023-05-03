<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use Spaze\NonceGenerator\Nonce;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TemplateFactoryTest extends TestCase
{

	public function __construct(
		private readonly Nonce $nonce,
		private readonly TemplateFactory $templateFactory,
	) {
	}


	public function testCreateTemplate(): void
	{
		$template = $this->templateFactory->createTemplate();
		$providers = $template->getLatte()->getProviders();
		Assert::hasKey('uiNonce', $providers);
		Assert::same($this->nonce->getValue(), $providers['uiNonce']);
	}

}

$runner->run(TemplateFactoryTest::class);
