<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

use Exception;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Utils\DateTime;

final class DateTimeParser
{

	public function getDaysFromString(string $interval): int
	{
		$now = new DateTime();
		try {
			$then = DateTime::from($interval);
		} catch (Exception $e) {
			throw new ShouldNotHappenException("Cannot create an object from {$interval}", previous: $e);
		}
		return $now->diff($then)->days;
	}

}
