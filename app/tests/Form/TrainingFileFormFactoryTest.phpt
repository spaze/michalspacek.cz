<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use DateTimeImmutable;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Arrays;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TrainingFileFormFactoryTest extends TestCase
{

	private readonly UiForm $form;
	private string $message = '';
	private string $type = '';


	public function __construct(
		TrainingFileFormFactory $formFactory,
		ApplicationPresenter $applicationPresenter,
	) {
		$this->form = $formFactory->create(
			function (Html|string $message, string $type) {
				$this->message = (string)$message;
				$this->type = $type;
			},
			new DateTimeImmutable(),
			[],
		);
		$presenter = $applicationPresenter->createUiPresenter('Admin:Trainings', 'foo', 'file');
		/** @noinspection PhpInternalEntityUsedInspection */
		$this->form->setParent($presenter);
	}


	public function testCreateOnSuccessError(): void
	{
		Arrays::invoke($this->form->onSuccess, $this->form);
		Assert::same('Soubor nebyl vybrán nebo došlo k nějaké chybě při nahrávání', $this->message);
		Assert::same('error', $this->type);
	}

}

TestCaseRunner::run(TrainingFileFormFactoryTest::class);
