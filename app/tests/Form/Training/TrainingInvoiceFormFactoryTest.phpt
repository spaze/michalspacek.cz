<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Training;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Database\ResultSet;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Arrays;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingInvoiceFormFactoryTest extends TestCase
{

	private ?int $count = null;


	public function __construct(
		private readonly TrainingInvoiceFormFactory $formFactory,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly Database $database,
	) {
	}


	public function testCreateOnSuccess(): void
	{
		$form = $this->formFactory->create(
			function (int $count): void {
				$this->count = $count;
			},
			function (): void {
				$this->count = null;
			},
			[11, 22],
		);
		$this->applicationPresenter->anchorForm($form);

		$this->database->setResultSet(new ResultSet(1));
		Arrays::invoke($form->onSuccess, $form);
		Assert::same(1, $this->count);

		$this->database->setResultSet(new ResultSet());
		Arrays::invoke($form->onSuccess, $form);
		Assert::null($this->count);
	}

}

TestCaseRunner::run(TrainingInvoiceFormFactoryTest::class);
