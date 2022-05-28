<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use MichalSpacekCz\Test\ServicesTrait;
use Spaze\NonceGenerator\Generator;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TemplateFactoryTest extends TestCase
{

	use ServicesTrait;


	private Generator $nonceGenerator;
	private TemplateFactory $templateFactory;


	protected function setUp()
	{
		$this->nonceGenerator = $this->getNonceGenerator();
		$this->templateFactory = $this->getTemplateFactory();
	}


	public function testCreateTemplate(): void
	{
		$template = $this->templateFactory->createTemplate();
		$providers = $template->getLatte()->getProviders();
		Assert::hasKey('uiNonce', $providers);
		Assert::same($this->nonceGenerator->getNonce(), $providers['uiNonce']);
	}

}

(new TemplateFactoryTest())->run();
