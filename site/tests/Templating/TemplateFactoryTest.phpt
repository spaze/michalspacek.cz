<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use Spaze\NonceGenerator\Generator;
use Tester\Assert;
use Tester\TestCase;

$container = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TemplateFactoryTest extends TestCase
{

	public function __construct(
		private readonly Generator $nonceGenerator,
		private readonly TemplateFactory $templateFactory,
	) {
	}


	public function testCreateTemplate(): void
	{
		$template = $this->templateFactory->createTemplate();
		$providers = $template->getLatte()->getProviders();
		Assert::hasKey('uiNonce', $providers);
		Assert::same($this->nonceGenerator->getNonce(), $providers['uiNonce']);
	}

}

(new TemplateFactoryTest(
	$container->getByType(Generator::class),
	$container->getByType(TemplateFactory::class),
))->run();
