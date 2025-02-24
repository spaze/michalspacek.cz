<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use DateTime;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Arrays;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class TrainingApplicationPreliminaryFormFactoryTest extends TestCase
{

	private const int TRAINING_ID = 1337;

	private ?string $action = null;
	private ?string $message = null;
	private UiForm $form;


	public function __construct(
		private readonly Database $database,
		TrainingApplicationPreliminaryFormFactory $formFactory,
		ApplicationPresenter $applicationPresenter,
	) {
		// For Statuses::getInitialStatuses()
		$database->setFetchPairsDefaultResult([
			2 => 'TENTATIVE',
		]);
		// For Statuses::getStatusId() in TrainingApplicationStorage::insertApplication()
		$database->addFetchFieldResult(2);
		// For TrainingApplicationSources::getSourceId in TrainingApplicationStorage::insertApplication()
		$database->addFetchFieldResult(3);
		// For Statuses::getStatusId() in Statuses::setStatus()
		$database->addFetchFieldResult(4);
		// For $prevStatus in Statuses::setStatus()
		$database->setFetchDefaultResult([
			'statusId' => 5,
			'statusTime' => new DateTime('-2 days'),
			'statusTimeTimeZone' => 'Europe/Prague',
		]);

		$this->form = $formFactory->create(
			function (string $action): void {
				$this->action = $action;
			},
			function (string $message): void {
				$this->message = $message;
			},
			self::TRAINING_ID,
			'action',
		);
		$applicationPresenter->anchorForm($this->form);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->action = $this->message = null;
	}


	public function testCreateOnSuccess(): void
	{
		Arrays::invoke($this->form->onSuccess, $this->form);
		Assert::same('action', $this->action);
		Assert::null($this->message);
		$params = $this->database->getParamsArrayForQuery('INSERT INTO training_applications');
		Assert::same(self::TRAINING_ID, $params[0]['key_training']);
	}


	public function testCreateOnSuccessSpam(): void
	{
		$this->form->setDefaults([
			'name' => 'lowercase',
		]);
		Arrays::invoke($this->form->onSuccess, $this->form);
		Assert::null($this->action);
		Assert::same('messages.trainings.spammyapplication', $this->message);
	}

}

TestCaseRunner::run(TrainingApplicationPreliminaryFormFactoryTest::class);
