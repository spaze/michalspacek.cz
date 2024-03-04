<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

use Exception;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Utils\DateTime as NetteDateTime;

class DateTime
{

	/**
	 * Same as DATE_RFC3339_EXTENDED except it uses microseconds (`.u`) instead of milliseconds (`.v`).
	 */
	public const string DATE_RFC3339_MICROSECONDS = 'Y-m-d\TH:i:s.uP';

	/**
	 * Same as in \Nette\Database\Drivers\MySqlDriver::formatDateTime() but without the quotes.
	 */
	public const string DATE_MYSQL = 'Y-m-d H:i:s';


	public function getDaysFromString(string $interval): int
	{
		$now = new NetteDateTime();
		try {
			$then = NetteDateTime::from($interval);
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
