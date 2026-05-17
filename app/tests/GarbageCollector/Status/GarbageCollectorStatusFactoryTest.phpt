<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\GarbageCollector\Status;

use DateTimeImmutable;
use MichalSpacekCz\GarbageCollector\GarbageCollector;
use MichalSpacekCz\GarbageCollector\GarbageCollectorReturnCode;
use MichalSpacekCz\GarbageCollector\GarbageCollectors;
use MichalSpacekCz\GarbageCollector\GarbageCollectorType;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\DateTime;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class GarbageCollectorStatusFactoryTest extends TestCase
{

	private const string NOW = '2026-05-17 12:00:00';
	private const string TIMEZONE = 'UTC';


	public function __construct(
		private readonly Database $database,
		private readonly DateTimeMachineFactory $dateTimeFactory,
	) {
		$this->dateTimeFactory->setDateTime(new DateTimeImmutable(self::NOW . ' ' . self::TIMEZONE));
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testCreateStatusesNoRowsMarksNoStatus(): void
	{
		$this->database->setFetchDefaultResult([]);
		$factory = $this->createFactory([$this->createGc(GarbageCollectorType::Sessions, 24 * 60 * 60)]);
		$statuses = $factory->createStatuses();
		Assert::count(1, $statuses);
		Assert::same(GarbageCollectorType::Sessions, $statuses[0]->type);
		Assert::false($statuses[0]->ok);
		Assert::true($statuses[0]->noStatus);
	}


	public function testCreateStatusesOkRowMarksOk(): void
	{
		$this->database->setFetchDefaultResult([
			'gcTime' => DateTime::from(self::NOW),
			'gcTimeTimezone' => self::TIMEZONE,
			'returnCode' => GarbageCollectorReturnCode::Ok->value,
			'message' => null,
		]);
		$factory = $this->createFactory([$this->createGc(GarbageCollectorType::Sessions, 24 * 60 * 60)]);
		$statuses = $factory->createStatuses();
		Assert::count(1, $statuses);
		Assert::true($statuses[0]->ok);
		Assert::false($statuses[0]->noStatus);
		Assert::false($statuses[0]->tooOld);
		Assert::same(self::NOW, $statuses[0]->lastRun?->format('Y-m-d H:i:s'));
	}


	public function testCreateStatusesOldRowMarksTooOld(): void
	{
		$this->database->setFetchDefaultResult([
			'gcTime' => DateTime::from('2026-05-10 12:00:00'), // 7 days ago
			'gcTimeTimezone' => self::TIMEZONE,
			'returnCode' => GarbageCollectorReturnCode::Ok->value,
			'message' => null,
		]);
		$factory = $this->createFactory([$this->createGc(GarbageCollectorType::Sessions, 24 * 60 * 60)]);
		$statuses = $factory->createStatuses();
		Assert::count(1, $statuses);
		Assert::false($statuses[0]->ok);
		Assert::true($statuses[0]->tooOld);
	}


	public function testCreateStatusesFailureRowMarksNotOk(): void
	{
		$this->database->setFetchDefaultResult([
			'gcTime' => DateTime::from(self::NOW),
			'gcTimeTimezone' => self::TIMEZONE,
			'returnCode' => GarbageCollectorReturnCode::Failure->value,
			'message' => 'something went wrong',
		]);
		$factory = $this->createFactory([$this->createGc(GarbageCollectorType::Sessions, 24 * 60 * 60)]);
		$statuses = $factory->createStatuses();
		Assert::count(1, $statuses);
		Assert::false($statuses[0]->ok);
		Assert::same('something went wrong', $statuses[0]->message);
	}


	public function testCreateStatusesUnknownReturnCodeMarksNotOkWithExplanation(): void
	{
		$this->database->setFetchDefaultResult([
			'gcTime' => DateTime::from(self::NOW),
			'gcTimeTimezone' => self::TIMEZONE,
			'returnCode' => 999,
			'message' => null,
		]);
		$factory = $this->createFactory([$this->createGc(GarbageCollectorType::Sessions, 24 * 60 * 60)]);
		$statuses = $factory->createStatuses();
		Assert::count(1, $statuses);
		Assert::false($statuses[0]->ok);
		Assert::same('Unknown return code 999', $statuses[0]->message);
	}


	public function testCreateStatusesUnknownReturnCodeKeepsOriginalMessage(): void
	{
		$this->database->setFetchDefaultResult([
			'gcTime' => DateTime::from(self::NOW),
			'gcTimeTimezone' => self::TIMEZONE,
			'returnCode' => 999,
			'message' => 'something else also happened',
		]);
		$factory = $this->createFactory([$this->createGc(GarbageCollectorType::Sessions, 24 * 60 * 60)]);
		$statuses = $factory->createStatuses();
		Assert::count(1, $statuses);
		Assert::false($statuses[0]->ok);
		Assert::same('Unknown return code 999 (something else also happened)', $statuses[0]->message);
	}


	/**
	 * @param list<GarbageCollector> $gcs
	 */
	private function createFactory(array $gcs): GarbageCollectorStatusFactory
	{
		return new GarbageCollectorStatusFactory(
			new GarbageCollectors($gcs),
			$this->database,
			$this->dateTimeFactory,
		);
	}


	private function createGc(GarbageCollectorType $type, int $interval): GarbageCollector
	{
		return new class ($type, $interval) implements GarbageCollector {

			public function __construct(
				private readonly GarbageCollectorType $type,
				private readonly int $interval,
			) {
			}


			#[Override]
			public function getGarbageCollectorType(): GarbageCollectorType
			{
				return $this->type;
			}


			#[Override]
			public function getIntervalSeconds(): int
			{
				return $this->interval;
			}


			#[Override]
			public function clean(): GarbageCollectorReturnCode
			{
				return GarbageCollectorReturnCode::Ok;
			}

		};
	}

}

TestCaseRunner::run(GarbageCollectorStatusFactoryTest::class);
