<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

use DateTimeImmutable;
use DateTimeZone;
use MichalSpacekCz\DateTime\Exceptions\CannotParseDateTimeException;

class DateTimeFactory
{

	/**
	 * Similar to \Nette\Utils\DateTime::createFromFormat() except this method returns \DateTimeImmutable.
	 *
	 * @throws CannotParseDateTimeException
	 */
	public static function createFromFormat(string $format, string $datetime, DateTimeZone $timezone = null): DateTimeImmutable
	{
		$date = DateTimeImmutable::createFromFormat($format, $datetime, $timezone);
		if ($date === false) {
			throw new CannotParseDateTimeException($format, $datetime);
		}
		return $date;
	}

}
