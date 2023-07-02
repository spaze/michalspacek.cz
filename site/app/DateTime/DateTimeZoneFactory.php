<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

use DateTimeZone;
use Exception;
use MichalSpacekCz\DateTime\Exceptions\InvalidTimezoneException;

class DateTimeZoneFactory
{

	/**
	 * @throws InvalidTimezoneException
	 */
	public function get(string $timezone): DateTimeZone
	{
		try {
			return new DateTimeZone($timezone);
		} catch (Exception $e) {
			throw new InvalidTimezoneException($timezone, $e);
		}
	}

}
