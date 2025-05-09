<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use DateTimeImmutable;
use Spaze\SecurityTxt\Fields\SecurityTxtExpires;

final class SecurityTxtExpired extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			'The file is considered stale and should not be used',
			[],
			'draft-foudil-securitytxt-09',
			new DateTimeImmutable('+1 year midnight -1 sec')->format(SecurityTxtExpires::FORMAT),
			'The `Expires` field should contain a date and time in the future formatted according to the Internet profile of ISO 8601 as defined in RFC 3339',
			[],
			'2.5.5',
			['5.3'],
		);
	}

}
