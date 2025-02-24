<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use DateTime;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\Training\TrainingTestDataFactory;
use MichalSpacekCz\Training\Dates\TrainingDateStatus;
use Nette\Utils\Arrays;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class TrainingApplicationAdminFormFactoryTest extends TestCase
{

	private const int TRAINING_DATE_ID = 14;
	private const string TRAINING_ACTION = 'action-1';

	private ?int $result = null;


	public function __construct(
		private readonly TrainingApplicationAdminFormFactory $formFactory,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly Database $database,
		private readonly TrainingTestDataFactory $dataFactory,
	) {
	}


	public function testCreateOnSuccess(): void
	{
		// For TrainingApplicationSources::getAll()
		$this->database->addFetchPairsResult([
			'foo-bar' => 'Foo Bar',
		]);
		// For TrainingApplicationStatuses::setStatus() => TrainingApplicationSources::getSourceId()
		$this->database->addFetchFieldResult(12);
		// For UpcomingTrainingDates::getPublicUpcoming() => UpcomingTrainingDates::getUpcoming()
		$this->database->addFetchAllResult([
			[
				'dateId' => self::TRAINING_DATE_ID,
				'trainingId' => 1,
				'action' => self::TRAINING_ACTION,
				'name' => 'Name',
				'price' => 2600,
				'studentDiscount' => null,
				'hasCustomPrice' => 0,
				'hasCustomStudentDiscount' => 0,
				'start' => new DateTime('+1 day'),
				'end' => new DateTime('+2 days'),
				'labelJson' => '{"cs_CZ": "lej-bl", "en_US": "la-bel"}',
				'public' => 1,
				'status' => TrainingDateStatus::Confirmed->value,
				'remote' => 0,
				'remoteUrl' => null,
				'remoteNotes' => null,
				'venueId' => 1,
				'venueAction' => 'venue-1',
				'venueHref' => 'https://venue.example',
				'venueName' => 'Venue name',
				'venueNameExtended' => 'Venue name extended',
				'venueAddress' => 'Address',
				'venueCity' => 'City',
				'venueDescription' => 'Venue **description**',
				'cooperationId' => 1,
				'cooperationDescription' => 'Co-op',
				'videoHref' => 'https://video.example',
				'feedbackHref' => 'https://feedback.example',
				'note' => 'Not-E',
			],
		]);

		$form = $this->formFactory->create(
			function (?int $dateId): void {
				$this->result = $dateId;
			},
			function (): void {
			},
			$this->dataFactory->getTrainingApplication(12, dateId: null, trainingAction: self::TRAINING_ACTION, sourceAlias: 'foo-bar'),
		);
		$this->applicationPresenter->anchorForm($form);
		$form->setDefaults([
			'date' => self::TRAINING_DATE_ID,
			'familiar' => true,
			'country' => 'cz',
			'price' => 13.37,
			'priceVat' => 14.47,
		]);
		Arrays::invoke($form->onSuccess, $form);
		Assert::same(self::TRAINING_DATE_ID, $this->result);
		$params = $this->database->getParamsArrayForQuery('UPDATE training_applications SET ? WHERE id_application = ?');
		Assert::true($params[0]['familiar']);
		Assert::same(self::TRAINING_DATE_ID, $params[0]['key_date']);
	}

}

TestCaseRunner::run(TrainingApplicationAdminFormFactoryTest::class);
