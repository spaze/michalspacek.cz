<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Training;

use DateTime;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\TrainingDateStatus;
use Nette\Utils\Arrays;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingDateFormFactoryTest extends TestCase
{

	private const int TRAINING_ID = 10;
	private const int COOPERATION_ID = 12;
	private const int TRAINING_DATE_ID = 14;


	private ?bool $resultAdd = null;
	private ?int $resultEditId = null;


	public function __construct(
		private readonly TrainingDateFormFactory $formFactory,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly Database $database,
	) {
	}


	#[Override]
	protected function setUp(): void
	{
		$training = [
			'id' => self::TRAINING_ID,
			'action' => '',
			'name' => 'Training name',
			'description' => '',
			'content' => '',
			'upsell' => '',
			'prerequisites' => '',
			'audience' => '',
			'capacity' => 20,
			'price' => null,
			'studentDiscount' => 40,
			'materials' => '',
			'custom' => 0,
			'successorId' => null,
			'discontinuedId' => null,
		];
		// For Trainings::getById()
		$this->database->setFetchDefaultResult($training);
		$this->database->addFetchAllResult([$training]);
		$this->database->addFetchAllResult([
			[
				'id' => 60,
				'name' => 'Venue name',
				'nameExtended' => '',
				'href' => '',
				'address' => '',
				'city' => '',
				'descriptionTexy' => '',
				'action' => '',
				'entrance' => '',
				'entranceNavigation' => '',
				'streetview' => '',
				'parkingTexy' => '',
				'publicTransportTexy' => '',
			],
		]);
		$this->database->addFetchAllResult([
			[
				'id' => 3,
				'status' => 'CONFIRMED',
				'description' => 'Displayed on the site with full date, regular signup',
			],
		]);
		$this->database->addFetchPairsResult([
			self::COOPERATION_ID => 'coop',
		]);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->resultAdd = null;
		$this->resultEditId = null;
	}


	public function testCreateOnSuccessAdd(): void
	{
		$form = $this->formFactory->create(
			function (): void {
				$this->resultAdd = true;
			},
			function (): void {
			},
			null,
		);
		$this->applicationPresenter->anchorForm($form);
		$form->setDefaults([
			'training' => self::TRAINING_ID,
			'status' => TrainingDateStatus::Confirmed->id(),
			'cooperation' => self::COOPERATION_ID,
		]);
		Arrays::invoke($form->onSuccess, $form);
		Assert::true($this->resultAdd);
		Assert::null($this->resultEditId);
	}


	public function testCreateOnSuccessEdit(): void
	{
		$form = $this->formFactory->create(
			function (): void {
			},
			function (int $dateId): void {
				$this->resultEditId = $dateId;
			},
			$this->buildTrainingDate(),
		);
		$this->applicationPresenter->anchorForm($form);
		$form->setDefaults([
			'training' => self::TRAINING_ID,
			'status' => TrainingDateStatus::Confirmed->id(),
			'cooperation' => self::COOPERATION_ID,
		]);
		Arrays::invoke($form->onSuccess, $form);
		Assert::null($this->resultAdd);
		Assert::same(self::TRAINING_DATE_ID, $this->resultEditId);
	}


	public function testCreateOnValidate(): void
	{
		$form = $this->formFactory->create(
			function (): void {
			},
			function (int $dateId): void {
				$this->resultEditId = $dateId;
			},
			$this->buildTrainingDate(),
		);
		$this->applicationPresenter->anchorForm($form);
		$form->setDefaults([
			'training' => self::TRAINING_ID,
			'status' => TrainingDateStatus::Confirmed->id(),
			'cooperation' => self::COOPERATION_ID,
		]);
		Arrays::invoke($form->onValidate, $form);
		Assert::null($this->resultAdd);
		Assert::null($this->resultEditId);
		Assert::same(['Běžná cena není nastavena, je třeba nastavit cenu zde'], $form->getErrors());
	}


	private function buildTrainingDate(): TrainingDate
	{
		return new TrainingDate(
			self::TRAINING_DATE_ID,
			'',
			self::TRAINING_ID,
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

TestCaseRunner::run(TrainingDateFormFactoryTest::class);
