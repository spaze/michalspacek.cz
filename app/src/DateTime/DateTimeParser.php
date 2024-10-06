<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

use Exception;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Utils\DateTime;

class DateTimeParser
{

	public function getDaysFromString(string $interval): int
	{
		$now = new DateTime();
		try {
			$then = DateTime::from($interval);
		} catch (Exception $e) {
			throw new ShouldNotHappenException("Cannot create an object from {$interval}", previous: $e);
		}
		$days = $now->diff($then)->days;
		if ($days === false) {
			throw new ShouldNotHappenException(sprintf('Cannot diff %s and %s', $now->format(DATE_RFC3339_EXTENDED), $then->format(DATE_RFC3339_EXTENDED)));
		}
		return $days;
	}

}
