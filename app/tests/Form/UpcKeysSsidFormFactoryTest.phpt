<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Form\FormComponents;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Arrays;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class UpcKeysSsidFormFactoryTest extends TestCase
{

	private readonly UiForm $form;
	private string $ssid = '';


	public function __construct(
		private readonly FormComponents $formComponents,
		UpcKeysSsidFormFactory $formFactory,
		ApplicationPresenter $applicationPresenter,
	) {
		$this->form = $formFactory->create(
			function (string $ssid) {
				$this->ssid = $ssid;
			},
			null,
		);
		$applicationPresenter->anchorForm($this->form);
	}


	public function testCreateOnSuccessError(): void
	{
		$this->formComponents->setValue($this->form, 'ssid', ' abc123 ');
		Arrays::invoke($this->form->onSuccess, $this->form);
		Assert::same('ABC123', $this->ssid);
	}

}

TestCaseRunner::run(UpcKeysSsidFormFactoryTest::class);
