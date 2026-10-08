<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\DateTime;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Lets a test say what "now" is, for factories that differ only in the timezone they default to.
 */
trait DateTimeMachine
{

	private ?DateTimeImmutable $dateTime = null;


	public function setDateTime(?DateTimeImmutable $dateTime): void
	{
		$this->dateTime = $dateTime;
	}


	/**
	 * Tests mock the "now" time, anything computed from it moves with the mock too. Only `+/-`-prefixed offsets
	 * (`create('-10 days')`) are recognised here, other PHP relative phrasings ('now - 30 days', 'yesterday',
	 * 'last Monday') fall through to the parent factory and reflect real time.
	 */
	public function create(string $datetime = 'now', ?DateTimeZone $timezone = null): DateTimeImmutable
	{
		if ($this->dateTime === null) {
			return parent::create($datetime, $timezone);
		}
		$trimmed = trim($datetime);
		// Apply the requested timezone to the base before modify() so relative offsets
		// are computed in that zone (matters across DST boundaries), matching the parent's
		// `new DateTimeImmutable($datetime, $timezone)` contract.
		$base = $timezone === null ? $this->dateTime : $this->dateTime->setTimezone($timezone);
		if ($trimmed === '' || strcasecmp($trimmed, 'now') === 0) {
			return $base;
		}
		if ($trimmed[0] === '+' || $trimmed[0] === '-') {
			return $base->modify($datetime);
		}
		return parent::create($datetime, $timezone);
	}

}
