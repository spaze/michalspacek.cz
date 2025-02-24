<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\DateTime;

use DateTimeImmutable;
use DateTimeZone;
use MichalSpacekCz\DateTime\DateTimeFactory;
use Override;

/**
 * This (date) time machine allows you you to time-travel.
 * Supposed to be used in tests to create DateTimeImmutable objects with known dates.
 */
final class DateTimeMachineFactory extends DateTimeFactory
{

	private ?DateTimeImmutable $dateTime = null;


	public function setDateTime(?DateTimeImmutable $dateTime): void
	{
		$this->dateTime = $dateTime;
	}


	#[Override]
	public function create(string $datetime = 'now', ?DateTimeZone $timezone = null): DateTimeImmutable
	{
		return $this->dateTime ?? parent::create($datetime, $timezone);
	}

}
