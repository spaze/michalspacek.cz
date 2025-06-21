<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class DefaultTemplateTest extends TestCase
{

	public function __construct(
		private readonly TemplateFactory $templateFactory,
		private readonly ApplicationPresenter $applicationPresenter,
	) {
	}


	public function testSetParameters(): void
	{
		$template = $this->templateFactory->createTemplate();
		$rand = rand();
		$result = $template->setParameters((object)['foo' => 'bar', 'baz' => $rand]);
		Assert::same($template, $result);
		Assert::same('bar', $template->foo);
		Assert::same($rand, $template->baz);
	}


	public function testFlashes(): void
	{
		$presenter = $this->applicationPresenter->createUiPresenter('Www:Homepage', 'Foo', 'bar');
		$presenter->flashMessage('waldo');
		$template = $presenter->getTemplate();
		assert($template instanceof DefaultTemplate);
		assert(isset($template->flashes[0]));
		Assert::same('waldo', $template->flashes[0]->message);
	}

}

TestCaseRunner::run(DefaultTemplateTest::class);
