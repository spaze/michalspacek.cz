<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Test\DateTime;

use DateTimeImmutable;
use MichalSpacekCz\DateTime\DateTimeZoneFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class DateTimeMachineFactoryTest extends TestCase
{

	public function __construct(
		private readonly DateTimeMachineFactory $factory,
		private readonly DateTimeZoneFactory $zones,
	) {
	}


	public function testCreateWithoutSetDateTimeDelegatesToParent(): void
	{
		$this->factory->setDateTime(null);
		Assert::same('2024-01-15', $this->factory->create('2024-01-15')->format('Y-m-d'));
	}


	public function testCreateReturnsSetDateTimeForNow(): void
	{
		$this->factory->setDateTime(new DateTimeImmutable('2026-05-17 12:00:00'));
		Assert::same('2026-05-17 12:00:00', $this->factory->create()->format('Y-m-d H:i:s'));
		Assert::same('2026-05-17 12:00:00', $this->factory->create('now')->format('Y-m-d H:i:s'));
		Assert::same('2026-05-17 12:00:00', $this->factory->create('')->format('Y-m-d H:i:s'));
		Assert::same('2026-05-17 12:00:00', $this->factory->create('NOW')->format('Y-m-d H:i:s'));
		Assert::same('2026-05-17 12:00:00', $this->factory->create('  nOw  ')->format('Y-m-d H:i:s'));
	}


	public function testCreateAppliesRelativeOffsetToSetDateTime(): void
	{
		$this->factory->setDateTime(new DateTimeImmutable('2026-05-17 12:00:00'));
		Assert::same('2026-05-03 12:00:00', $this->factory->create('-14 days')->format('Y-m-d H:i:s'));
		Assert::same('2026-05-17 13:30:00', $this->factory->create('+90 minutes')->format('Y-m-d H:i:s'));
		Assert::same('2026-04-12 12:00:00', $this->factory->create('  -35 days')->format('Y-m-d H:i:s'));
	}


	public function testCreateWithAbsoluteDatetimeIgnoresSetDateTime(): void
	{
		$this->factory->setDateTime(new DateTimeImmutable('2026-05-17 12:00:00'));
		Assert::same('2020-11-22 00:00:00', $this->factory->create('2020-11-22')->format('Y-m-d H:i:s'));
	}


	public function testCreateAppliesTimezoneOnMockedPath(): void
	{
		$this->factory->setDateTime(new DateTimeImmutable('2026-05-17 12:00:00', $this->zones->get('UTC')));
		$prague = $this->zones->get('Europe/Prague');
		Assert::same('2026-05-17 14:00:00', $this->factory->create('now', $prague)->format('Y-m-d H:i:s'));
		Assert::same('2026-05-03 14:00:00', $this->factory->create('-14 days', $prague)->format('Y-m-d H:i:s'));
	}

}

TestCaseRunner::run(DateTimeMachineFactoryTest::class);
