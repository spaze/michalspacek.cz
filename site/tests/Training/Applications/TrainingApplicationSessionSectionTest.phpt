<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use DateTime;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\Applications\TrainingApplicationSessionSection;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\TrainingDateStatus;
use MichalSpacekCz\Training\Files\TrainingFiles;
use MichalSpacekCz\Training\Mails\TrainingMailMessageFactory;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingApplicationSessionSectionTest extends TestCase
{

	private const APPLICATION_ID = 303;
	private const DATE_ID = 909;
	private const NAME = 'Foo';
	private const EMAIL = 'foo@example.example';
	private const COMPANY = 'Teh Company';
	private const STREET = 'Street';
	private const CITY = 'City';
	private const ZIP = '303808';
	private const COUNTRY = 'Country';
	private const COMPANY_ID = '31337';
	private const COMPANY_TAX_ID = 'CZ31337';
	private const NOTE = 'Note';

	private TrainingApplicationSessionSection $trainingApplicationSessionSection;
	private SessionSection $sessionSection;


	public function __construct(
		private readonly Session $sessionHandler,
		private readonly Statuses $trainingStatuses,
		private readonly TrainingMailMessageFactory $trainingMailMessageFactory,
		private readonly TrainingFiles $trainingFiles,
	) {
	}


	protected function setUp(): void
	{
		$trainingApplicationSessionSection = $this->sessionHandler->getSection('training', TrainingApplicationSessionSection::class);
		if (!$trainingApplicationSessionSection instanceof TrainingApplicationSessionSection) {
			throw new ShouldNotHappenException();
		}
		$this->trainingApplicationSessionSection = $trainingApplicationSessionSection;
		$this->sessionSection = $this->sessionHandler->getSection('training');
	}


	protected function tearDown(): void
	{
		$this->sessionSection->remove();
	}


	public function testSetApplicationForTraining(): void
	{
		$application = $this->buildApplication();
		$this->sessionSection->set('application', ['foo' => 'bar']);
		$this->trainingApplicationSessionSection->setApplicationForTraining('training-action', $application);
		Assert::same(['foo' => 'bar', 'training-action' => ['id' => $application->getId(), 'dateId' => $application->getDateId()]], $this->sessionSection->get('application'));
		Assert::same($this->sessionSection->get('name'), $application->getName());
		Assert::same($this->sessionSection->get('email'), $application->getEmail());
		Assert::same($this->sessionSection->get('company'), $application->getCompany());
		Assert::same($this->sessionSection->get('street'), $application->getStreet());
		Assert::same($this->sessionSection->get('city'), $application->getCity());
		Assert::same($this->sessionSection->get('zip'), $application->getZip());
		Assert::same($this->sessionSection->get('country'), $application->getCountry());
		Assert::same($this->sessionSection->get('companyId'), $application->getCompanyId());
		Assert::same($this->sessionSection->get('companyTaxId'), $application->getCompanyTaxId());
		Assert::same($this->sessionSection->get('note'), $application->getNote());
	}


	public function testGetApplicationIdByDateId(): void
	{
		$trainingAction = 'training-action';
		$dateId = 303;
		Assert::null($this->trainingApplicationSessionSection->getApplicationIdByDateId($trainingAction, $dateId));

		$this->sessionSection->set('application', 'not an array');
		Assert::exception(function () use ($trainingAction, $dateId): void {
			$this->trainingApplicationSessionSection->getApplicationIdByDateId($trainingAction, $dateId);
		}, ShouldNotHappenException::class, "Session key application type should be an array, but it's a string");

		$this->sessionSection->set('application', ['foo' => 'bar']);
		Assert::null($this->trainingApplicationSessionSection->getApplicationIdByDateId($trainingAction, $dateId));

		$this->sessionSection->set('application', [$trainingAction => 'not an array']);
		Assert::exception(function () use ($trainingAction, $dateId): void {
			$this->trainingApplicationSessionSection->getApplicationIdByDateId($trainingAction, $dateId);
		}, ShouldNotHappenException::class, "Session key application > {$trainingAction} type should be array, but it's a string");

		$this->sessionSection->set('application', [$trainingAction => []]);
		Assert::null($this->trainingApplicationSessionSection->getApplicationIdByDateId($trainingAction, $dateId));

		$this->sessionSection->set('application', [$trainingAction => ['dateId' => 808]]);
		Assert::null($this->trainingApplicationSessionSection->getApplicationIdByDateId($trainingAction, $dateId));

		$this->sessionSection->set('application', [$trainingAction => ['dateId' => $dateId, 'id' => 'not an int']]);
		Assert::exception(function () use ($trainingAction, $dateId): void {
			$this->trainingApplicationSessionSection->getApplicationIdByDateId($trainingAction, $dateId);
		}, ShouldNotHappenException::class, "Session key application > {$trainingAction} > id type should be int, but it's a string");

		$this->sessionSection->set('application', [$trainingAction => ['dateId' => $dateId, 'id' => 31337]]);
		Assert::same(31337, $this->trainingApplicationSessionSection->getApplicationIdByDateId($trainingAction, $dateId));
	}


	public function testRemoveApplication(): void
	{
		$trainingAction = 'training-action';

		Assert::noError(function () use ($trainingAction): void {
			$this->trainingApplicationSessionSection->removeApplication($trainingAction);
		});

		$this->sessionSection->set('application', 'not an array');
		Assert::exception(function () use ($trainingAction): void {
			$this->trainingApplicationSessionSection->removeApplication($trainingAction);
		}, ShouldNotHappenException::class, "Session key application type should be array, but it's a string");

		$this->sessionSection->set('application', [$trainingAction => ['foo' => 'bar']]);
		$this->trainingApplicationSessionSection->removeApplication($trainingAction);
		Assert::same([$trainingAction => null], $this->sessionSection->get('application'));
	}


	public function testSetOnSuccess(): void
	{
		$trainingDate = $this->buildTrainingDate();
		$this->trainingApplicationSessionSection->setOnSuccess($trainingDate, (object)$this->buildValues());
		Assert::same(self::DATE_ID, $this->sessionSection->get('trainingId'));
		Assert::same('Name', $this->sessionSection->get('name'));
		Assert::same('Email', $this->sessionSection->get('email'));
		Assert::same('Company', $this->sessionSection->get('company'));
		Assert::same('Street', $this->sessionSection->get('street'));
		Assert::same('City', $this->sessionSection->get('city'));
		Assert::same('Zip', $this->sessionSection->get('zip'));
		Assert::same('Country', $this->sessionSection->get('country'));
		Assert::same('CompanyId', $this->sessionSection->get('companyId'));
		Assert::same('CompanyTaxId', $this->sessionSection->get('companyTaxId'));
		Assert::same('Note', $this->sessionSection->get('note'));
	}


	public function testGetDateId(): void
	{
		Assert::null($this->trainingApplicationSessionSection->getDateId());

		$this->sessionSection->set('trainingId', "I'm a teapot");
		Assert::exception(function (): void {
			$this->trainingApplicationSessionSection->getDateId();
		}, ShouldNotHappenException::class, "Session key trainingId type should be null|int, but it's a string");

		$this->sessionSection->set('trainingId', 418);
		Assert::same(418, $this->trainingApplicationSessionSection->getDateId());
	}


	public function testGetApplicationValues(): void
	{
		$values = $this->buildValues();
		foreach ($values as $key => $value) {
			$this->sessionSection->set($key, $value);
		}
		Assert::same($values, $this->trainingApplicationSessionSection->getApplicationValues());
	}


	public function testRemoveApplicationValues(): void
	{
		$values = $this->buildValues();
		foreach ($values as $key => $value) {
			$this->sessionSection->set($key, $value);
		}
		$this->trainingApplicationSessionSection->removeApplicationValues();
		foreach (array_keys($values) as $key) {
			Assert::null($this->sessionSection->get($key), $key);
		}
	}


	private function buildApplication(): TrainingApplication
	{
		return new TrainingApplication(
			$this->trainingStatuses,
			$this->trainingMailMessageFactory,
			$this->trainingFiles,
			self::APPLICATION_ID,
			self::NAME,
			self::EMAIL,
			false,
			self::COMPANY,
			self::STREET,
			self::CITY,
			self::ZIP,
			self::COUNTRY,
			self::COMPANY_ID,
			self::COMPANY_TAX_ID,
			self::NOTE,
			'ATTENDED',
			new DateTime(),
			true,
			false,
			false,
			self::DATE_ID,
			null,
			'action',
			Html::fromText('Name'),
			null,
			null,
			false,
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
			'',
			'',
			null,
			null,
			null,
			'accessToken',
			'michal-spacek',
			'Michal Špaček',
			'MŠ',
		);
	}


	private function buildTrainingDate(): TrainingDate
	{
		return new TrainingDate(
			self::DATE_ID,
			'',
			1,
			true,
			false,
			new DateTime(),
			new DateTime(),
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


	/**
	 * @return array<string, string>
	 */
	private function buildValues(): array
	{
		return [
			'name' => 'Name',
			'email' => 'Email',
			'company' => 'Company',
			'street' => 'Street',
			'city' => 'City',
			'zip' => 'Zip',
			'country' => 'Country',
			'companyId' => 'CompanyId',
			'companyTaxId' => 'CompanyTaxId',
			'note' => 'Note',
		];
	}

}

TestCaseRunner::run(TrainingApplicationSessionSectionTest::class);
