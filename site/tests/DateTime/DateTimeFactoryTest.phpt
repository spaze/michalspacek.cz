<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

use DateTimeImmutable;
use MichalSpacekCz\DateTime\Exceptions\InvalidTimezoneException;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class DateTimeFactoryTest extends TestCase
{

	public function __construct(
		private readonly DateTimeFactory $dateTimeFactory,
	) {
	}


	public function testCreateFrom(): void
	{
		Assert::exception(function (): void {
			$this->dateTimeFactory->createFrom(new DateTimeImmutable(), 'Europe/Brno');
		}, InvalidTimezoneException::class, "Invalid timezone 'Europe/Brno'");

		$niceDate = '2020-10-10 20:30:40';
		$niceDateTime = new DateTimeImmutable("{$niceDate} UTC");
		$newDateTime = $this->dateTimeFactory->createFrom($niceDateTime, 'Europe/Prague');
		Assert::same($niceDate, $newDateTime->format('Y-m-d H:i:s'));
		Assert::same('Europe/Prague', $newDateTime->getTimezone()->getName());
		Assert::notSame($niceDateTime->getTimestamp(), $newDateTime->getTimestamp());
	}

}

$runner->run(DateTimeFactoryTest::class);
