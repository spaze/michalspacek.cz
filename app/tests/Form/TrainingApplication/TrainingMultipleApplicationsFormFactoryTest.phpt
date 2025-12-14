<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\TrainingApplication;

use DateTime;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatus;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\TrainingDateStatus;
use Nette\Utils\Arrays;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingMultipleApplicationsFormFactoryTest extends TestCase
{

	private const int TRAINING_DATE_ID = 14;
	private const int NEW_STATUS_ID = 15;


	private ?int $result = null;


	public function __construct(
		private readonly TrainingMultipleApplicationsFormFactory $formFactory,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly Database $database,
	) {
	}


	public function testCreateOnSuccess(): void
	{
		// For TrainingApplicationStatuses::getChildrenStatuses()
		$this->database->addFetchPairsResult([
			self::NEW_STATUS_ID => TrainingApplicationStatus::Attended->value,
		]);
		// For TrainingApplicationSources::getAll()
		$this->database->addFetchPairsResult([
			'foo-bar' => 'Foo Bar',
		]);
		// For TrainingApplicationStatuses::getStatusId()
		$this->database->addFetchFieldResult(self::NEW_STATUS_ID);
		// For TrainingApplicationSources::getSourceId()
		$this->database->addFetchFieldResult(11);
		// For TrainingApplicationStatuses::setStatus() => TrainingApplicationSources::getSourceId()
		$this->database->addFetchFieldResult(12);
		// For TrainingApplicationStatuses::setStatus()
		$dateTime = new DateTime();
		$this->database->addFetchResult([
			'statusId' => 17,
			'statusTime' => $dateTime,
			'statusTimeTimeZone' => $dateTime->getTimezone()->getName(),
		]);

		$form = $this->formFactory->create(
			function (int $dateId): void {
				$this->result = $dateId;
			},
			$this->buildTrainingDate(),
		);
		$form->setDefaults([
			'status' => TrainingApplicationStatus::Attended->value,
			'source' => 'foo-bar',
			'country' => 'cz',
		]);
		$this->applicationPresenter->anchorForm($form);
		Arrays::invoke($form->onSuccess, $form);
		Assert::same(self::TRAINING_DATE_ID, $this->result);
		$params = $this->database->getParamsArrayForQuery('INSERT INTO training_applications');
		Assert::same(self::TRAINING_DATE_ID, $params[0]['key_date']);
		Assert::same(self::NEW_STATUS_ID, $params[0]['key_status']);
	}


	private function buildTrainingDate(): TrainingDate
	{
		return new TrainingDate(
			self::TRAINING_DATE_ID,
			'',
			10,
			true,
			false,
			new DateTime('2024-10-20 10:00:00'),
			new DateTime('2024-10-21 18:00:00'),
			null,
			null,
			true,
			TrainingDateStatus::Confirmed,
			'',
			false,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			false,
			null,
			false,
			null,
			null,
			null,
			null,
		);
	}

}

TestCaseRunner::run(TrainingMultipleApplicationsFormFactoryTest::class);
