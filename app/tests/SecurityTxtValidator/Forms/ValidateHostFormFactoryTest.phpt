<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Forms;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Form\FormComponents;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Forms\Form;
use Nette\Utils\Arrays;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class ValidateHostFormFactoryTest extends TestCase
{

	private readonly Form $form;
	private string $host = '';


	public function __construct(
		private readonly FormComponents $formComponents,
		ValidateHostFormFactory $formFactory,
		ApplicationPresenter $applicationPresenter,
	) {
		$this->form = $formFactory->create(
			function (string $host) {
				$this->host = $host;
			},
			null,
			'/',
		);
		$applicationPresenter->anchorForm($this->form);
	}


	public function testCreateOnSuccessError(): void
	{
		$this->formComponents->setValue($this->form, 'host', ' example.com/FOO-bar ');
		Arrays::invoke($this->form->onSuccess, $this->form);
		Assert::same('example.com/FOO-bar', $this->host);
	}

}

TestCaseRunner::run(ValidateHostFormFactoryTest::class);
