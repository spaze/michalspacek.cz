<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use MichalSpacekCz\DateTime\Exceptions\CannotCreateDateTimeObjectException;
use MichalSpacekCz\DateTime\Exceptions\CannotParseDateTimeException;

readonly class DateTimeFactory
{

	public function __construct(
		private DateTimeZoneFactory $dateTimeZoneFactory,
	) {
	}


	/**
	 * Similar to \Nette\Utils\DateTime::createFromFormat() except this method returns \DateTimeImmutable.
	 *
	 * @throws CannotParseDateTimeException
	 */
	public function createFromFormat(string $format, string $datetime, ?DateTimeZone $timezone = null): DateTimeImmutable
	{
		$date = DateTimeImmutable::createFromFormat($format, $datetime, $timezone);
		if ($date === false) {
			throw new CannotParseDateTimeException($format, $datetime);
		}
		return $date;
	}


	/**
	 * @throws CannotCreateDateTimeObjectException
	 */
	public function createFrom(DateTimeInterface $dateTime, string $timezoneId): DateTimeImmutable
	{
		try {
			return new DateTimeImmutable($dateTime->format('Y-m-d H:i:s.u'), $this->dateTimeZoneFactory->get($timezoneId));
		} catch (Exception $e) {
			throw new CannotCreateDateTimeObjectException($e);
		}
	}

}
