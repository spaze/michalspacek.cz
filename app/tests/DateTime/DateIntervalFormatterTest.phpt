<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

use DateInterval;
use DateTimeImmutable;
use MichalSpacekCz\Test\NoOpTranslator;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class DateIntervalFormatterTest extends TestCase
{

	public function __construct(
		private readonly DateIntervalFormatter $dateIntervalFormatter,
		private readonly NoOpTranslator $translator,
	) {
	}


	/**
	 * @return list<array{0:string, 1:string, 2:int}>
	 */
	public function getIntervals(): array
	{
		return [
			['PT0S', 'messages.timeIntervalAgo.seconds', 0],
			['PT1S', 'messages.timeIntervalAgo.seconds', 1],
			['PT120S', 'messages.timeIntervalAgo.seconds', 120],
			['PT0M', 'messages.timeIntervalAgo.seconds', 0],
			['PT1M', 'messages.timeIntervalAgo.minutes', 1],
			['PT105M', 'messages.timeIntervalAgo.minutes', 105],
		];
	}


	/**
	 * @dataProvider getIntervals
	 */
	public function testToMinutesSecondsInterval(string $interval, string $expectedMessage, int $expectedNumber): void
	{
		Assert::same($expectedMessage, $this->dateIntervalFormatter->toMinutesSecondsAgo(new DateInterval($interval)));
		Assert::same([$expectedNumber], $this->translator->getParameters($expectedMessage)[0]);
	}


	/**
	 * @return list<array{0:string, 1:string, 2:int}>
	 */
	public function getIntervalsIn(): array
	{
		return [
			['PT0S', 'messages.timeIntervalIn.seconds', 0],
			['PT1S', 'messages.timeIntervalIn.seconds', 1],
			['PT30S', 'messages.timeIntervalIn.seconds', 30],
			['PT1M', 'messages.timeIntervalIn.minutes', 1],
			['PT105M', 'messages.timeIntervalIn.minutes', 105],
		];
	}


	/**
	 * @dataProvider getIntervalsIn
	 */
	public function testToMinutesSecondsIn(string $interval, string $expectedMessage, int $expectedNumber): void
	{
		Assert::same($expectedMessage, $this->dateIntervalFormatter->toMinutesSecondsIn(new DateInterval($interval)));
		Assert::same([$expectedNumber], $this->translator->getParameters($expectedMessage)[0]);
	}


	/**
	 * @return list<array{0:string, 1:string, 2:int}>
	 */
	public function getModifiers(): array
	{
		return [
			['-1 second', 'messages.timeIntervalAgo.seconds', 1],
			['-59 second', 'messages.timeIntervalAgo.seconds', 59],
			['-60 second', 'messages.timeIntervalAgo.minutes', 1],
			['-105 second', 'messages.timeIntervalAgo.minutes', 1],
			['-120 second', 'messages.timeIntervalAgo.minutes', 2],
			['-2 hours', 'messages.timeIntervalAgo.minutes', 120],
			['-2 hours -3 minutes', 'messages.timeIntervalAgo.minutes', 123],
			['-3 days -1 minute', 'messages.timeIntervalAgo.minutes', 4321],
		];
	}


	/**
	 * @dataProvider getModifiers
	 */
	public function testToMinutesSecondsDiff(string $interval, string $expectedMessage, int $expectedNumber): void
	{
		$now = new DateTimeImmutable('now');
		Assert::same($expectedMessage, $this->dateIntervalFormatter->toMinutesSecondsAgo($now->diff($now->modify($interval))));
		Assert::same([$expectedNumber], $this->translator->getParameters($expectedMessage)[0]);
	}

}

TestCaseRunner::run(DateIntervalFormatterTest::class);
