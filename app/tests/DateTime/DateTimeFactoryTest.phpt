<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

use DateTimeImmutable;
use MichalSpacekCz\DateTime\Exceptions\CannotCreateDateTimeObjectException;
use MichalSpacekCz\DateTime\Exceptions\CannotParseDateTimeException;
use MichalSpacekCz\DateTime\Exceptions\InvalidTimezoneException;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class DateTimeFactoryTest extends TestCase
{

	public function __construct(
		private readonly DateTimeFactory $dateTimeFactory,
		private readonly DateTimeZoneFactory $dateTimeZoneFactory,
	) {
	}


	public function testCreateFromFormat(): void
	{
		$dateTime = $this->dateTimeFactory->createFromFormat('j-M-Y', '15-Feb-2009');
		Assert::same('2009', $dateTime->format('Y'));
		Assert::same('02', $dateTime->format('m'));
		Assert::same('15', $dateTime->format('d'));

		Assert::exception(function (): void {
			$this->dateTimeFactory->createFromFormat('foo', '15-Feb-2009');
		}, CannotParseDateTimeException::class, "~^Cannot parse '15-Feb-2009' using format 'foo'~");
	}


	public function testCreateFrom(): void
	{
		$niceDate = '2020-10-10 20:30:40';
		$niceDateTime = new DateTimeImmutable("{$niceDate} UTC");
		$newDateTime = $this->dateTimeFactory->createFrom($niceDateTime, 'Europe/Prague');
		Assert::same($niceDate, $newDateTime->format(DateTimeFormat::MYSQL));
		Assert::same('Europe/Prague', $newDateTime->getTimezone()->getName());
		Assert::notSame($niceDateTime->getTimestamp(), $newDateTime->getTimestamp());
	}


	public function testCreateFromInvalidTimezone(): void
	{
		$e = Assert::exception(function (): void {
			$this->dateTimeFactory->createFrom(new DateTimeImmutable(), 'Europe/Brno');
		}, CannotCreateDateTimeObjectException::class, 'Cannot create a DateTime or DateTimeImmutable object');
		if (!$e instanceof CannotCreateDateTimeObjectException) {
			Assert::fail('Exception is of a wrong type ' . get_debug_type($e));
		} else {
			$previous = $e->getPrevious();
			if (!$previous instanceof InvalidTimeZoneException) {
				Assert::fail('Previous exception is of a wrong type ' . get_debug_type($previous));
			} else {
				Assert::same("Invalid timezone 'Europe/Brno'", $previous->getMessage());
			}
		}
	}


	public function testCreate(): void
	{
		Assert::same(
			'2024-11-12T02:03:04.000000+01:00',
			$this->dateTimeFactory->create('2024-11-12 02:03:04', $this->dateTimeZoneFactory->get('Europe/Amsterdam'))->format(DateTimeFormat::RFC3339_MICROSECONDS),
		);
	}

}

TestCaseRunner::run(DateTimeFactoryTest::class);
