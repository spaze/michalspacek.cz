<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\Training\TrainingTestDataFactory;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatus;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Arrays;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class TrainingApplicationStatusesFormFactoryTest extends TestCase
{

	private const int APPLICATION_ID = 1337;
	private const int STATUS_ID = 121;

	private UiForm $form;
	private bool|string|null $result = null;


	public function __construct(
		private readonly Database $database,
		private readonly TrainingTestDataFactory $dataFactory,
		TrainingApplicationStatusesFormFactory $formFactory,
		ApplicationPresenter $applicationPresenter,
		DateTimeMachineFactory $dateTimeFactory,
	) {
		$dateTime = new DateTimeImmutable('2024-12-17 12:10:48');
		$dateTimeFactory->setDateTime($dateTime);
		// For TrainingApplicationStatuses::getStatusId()
		$this->database->addFetchFieldResult(self::STATUS_ID);
		// For TrainingApplicationStatuses::getChildrenStatuses()
		$this->database->addFetchPairsResult([
			15 => TrainingApplicationStatus::Attended->value,
		]);

		$this->form = $formFactory->create(
			function (Html|null $message): void {
				if ($message === null) {
					$this->result = true;
				} else {
					$this->result = $message->toHtml();
				}
			},
			[$dataFactory->getTrainingApplication(self::APPLICATION_ID, status: TrainingApplicationStatus::Reminded)],
		);
		$applicationPresenter->anchorForm($this->form);
		$this->form->setDefaults([
			'applications' => [
				self::APPLICATION_ID => TrainingApplicationStatus::Attended->value,
			],
		]);
	}


	public function testCreateOnClickSubmit(): void
	{
		$statusDateTime = new DateTime('2024-10-20 05:06:07');
		// For TrainingApplicationStatuses::setStatus()
		$this->database->addFetchResult([
			'statusId' => 15, // REMINDED
			'statusTime' => $statusDateTime,
			'statusTimeTimeZone' => $statusDateTime->getTimezone()->getName(),
		]);
		// For TrainingApplicationStatuses::getDiscardedStatuses()
		$this->database->addFetchFieldResult(self::STATUS_ID + 1);
		// For TrainingApplicationStatuses::getAllowFilesStatuses()
		$this->database->addFetchFieldResult(self::STATUS_ID + 2);
		$this->database->addFetchFieldResult(self::STATUS_ID + 3);
		$this->database->addFetchFieldResult(self::STATUS_ID + 4);
		$submit = $this->form->getComponent('submit');
		assert($submit instanceof SubmitButton);
		Arrays::invoke($submit->onClick);
		Assert::true($this->result);
		Assert::same([
			[
				'key_status' => self::STATUS_ID,
				'status_time' => '2024-12-17 12:10:48',
				'status_time_timezone' => 'Europe/Prague',
			],
		], $this->database->getParamsArrayForQuery('UPDATE training_applications SET ? WHERE id_application = ?'));
	}


	public function testCreateOnClickFamiliar(): void
	{
		$this->database->addFetchResult($this->dataFactory->getDatabaseResultData(self::APPLICATION_ID));
		$statusDateTime = new DateTime('2024-10-20 07:08:09');
		// For TrainingApplicationStatuses::setStatus()
		$this->database->addFetchResult([
			'statusId' => 15, // REMINDED
			'statusTime' => $statusDateTime,
			'statusTimeTimeZone' => $statusDateTime->getTimezone()->getName(),
		]);
		$familiar = $this->form->getComponent('familiar');
		assert($familiar instanceof SubmitButton);
		Arrays::invoke($familiar->onClick);
		Assert::same('Tykání nastaveno pro 1 účastníků ve stavu <code>ATTENDED</code>', $this->result);
		Assert::same([self::APPLICATION_ID], $this->database->getParamsForQuery('UPDATE training_applications SET familiar = TRUE WHERE id_application = ?'));
	}

}

TestCaseRunner::run(TrainingApplicationStatusesFormFactoryTest::class);
