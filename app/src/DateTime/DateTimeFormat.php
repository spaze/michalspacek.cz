<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

class DateTimeFormat
{

	/**
	 * Same as DATE_RFC3339_EXTENDED except it uses microseconds (`.u`) instead of milliseconds (`.v`).
	 */
	public const string RFC3339_MICROSECONDS = 'Y-m-d\TH:i:s.uP';

	/**
	 * Same as in \Nette\Database\Drivers\MySqlDriver::formatDateTime() but without the quotes.
	 */
	public const string MYSQL = 'Y-m-d H:i:s';

}
