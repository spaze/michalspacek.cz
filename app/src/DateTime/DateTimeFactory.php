<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use MichalSpacekCz\DateTime\Exceptions\CannotCreateDateTimeObjectException;
use MichalSpacekCz\DateTime\Exceptions\CannotParseDateTimeException;

class DateTimeFactory
{

	private ?DateTimeZone $defaultTimeZone = null;


	public function __construct(
		private readonly DateTimeZoneFactory $dateTimeZoneFactory,
		?string $defaultTimezoneId = null,
	) {
		if ($defaultTimezoneId !== null) {
			$this->defaultTimeZone = $this->dateTimeZoneFactory->get($defaultTimezoneId);
		}
	}


	/**
	 * Similar to \Nette\Utils\DateTime::createFromFormat() except this method returns \DateTimeImmutable.
	 *
	 * @throws CannotParseDateTimeException
	 */
	public function createFromFormat(string $format, string $datetime, ?DateTimeZone $timezone = null): DateTimeImmutable
	{
		$date = DateTimeImmutable::createFromFormat($format, $datetime, $timezone ?? $this->defaultTimeZone);
		if ($date === false) {
			throw new CannotParseDateTimeException($format, $datetime);
		}
		return $date;
	}


	/**
	 * @throws CannotCreateDateTimeObjectException
	 */
	public function createFrom(DateTimeInterface $dateTime, ?string $timezoneId = null): DateTimeImmutable
	{
		try {
			$timezone = $timezoneId === null ? $this->defaultTimeZone : $this->dateTimeZoneFactory->get($timezoneId);
			return new DateTimeImmutable($dateTime->format('Y-m-d H:i:s.u'), $timezone);
		} catch (Exception $e) {
			throw new CannotCreateDateTimeObjectException($e);
		}
	}


	public function create(string $datetime = 'now', ?DateTimeZone $timezone = null): DateTimeImmutable
	{
		return new DateTimeImmutable($datetime, $timezone ?? $this->defaultTimeZone);
	}


	public function getNow(): DateTimeImmutable
	{
		return $this->create('now', $this->defaultTimeZone);
	}

}
