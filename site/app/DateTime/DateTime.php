<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

class DateTime
{

	/**
	 * Same as DATE_RFC3339_EXTENDED except it uses microseconds (`.u`) instead of milliseconds (`.v`).
	 *
	 * @var string
	 */
	public const DATE_RFC3339_MICROSECONDS = 'Y-m-d\TH:i:s.uP';

}
