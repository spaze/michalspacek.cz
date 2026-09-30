<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Statistics;

use DateTimeImmutable;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactoryUtc;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class StatisticsTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly DateTimeMachineFactoryUtc $dateTime,
		private readonly NullLogger $logger,
		private readonly Statistics $statistics,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->dateTime->setDateTime(null);
		$this->logger->reset();
	}


	/**
	 * One statement for however many counters a response touches, each row either started at one or counted up where
	 * it is, so a day's counters are never read and written back and two requests cannot lose each other's count.
	 */
	public function testCountersAreCountedUpInOneStatementForTheUtcDay(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2025-05-01 12:00:00'));
		$this->statistics->increment(StatisticsVolume::Fetched, StatisticsVerdict::Valid);
		Assert::same([[
			['day' => '2025-05-01', 'metric' => 'volume', 'bucket' => 'fetched', 'count' => 1],
			['day' => '2025-05-01', 'metric' => 'verdict', 'bucket' => 'valid', 'count' => 1],
		]], $this->database->getParamsArrayForQuery('INSERT INTO statistics'));
		Assert::same(['ON DUPLICATE KEY UPDATE count = count + 1'], $this->database->getParamsForQuery('INSERT INTO statistics'));
	}


	public function testNothingIsWrittenWhenThereIsNothingToCount(): void
	{
		$this->statistics->increment();
		Assert::same([], $this->database->getParamsArrayForQuery('INSERT INTO statistics'));
		Assert::same([], $this->database->getParamsForQuery('INSERT INTO statistics'));
	}


	/**
	 * The visitor came for a result, and a counter that cannot be written is not worth taking it away from them.
	 */
	public function testACounterThatCannotBeWrittenIsLoggedNotThrown(): void
	{
		$failure = new RuntimeException('Statistics gone');
		$this->database->willThrow($failure);
		Assert::noError(function (): void {
			$this->statistics->increment(StatisticsVolume::Pasted);
		});
		Assert::same([$failure], $this->logger->getLogged());
	}

}

TestCaseRunner::run(StatisticsTest::class);
