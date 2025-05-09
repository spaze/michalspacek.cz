<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use DateTimeImmutable;
use Spaze\SecurityTxt\Fields\SecurityTxtExpires;

final class SecurityTxtNoExpires extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			'The `Expires` field must always be present',
			[],
			'draft-foudil-securitytxt-10',
			new DateTimeImmutable('+1 year midnight -1 sec')->format(SecurityTxtExpires::FORMAT),
			'Add an `Expires` field with a date and time in the future formatted according to the Internet profile of ISO 8601 as defined in RFC 3339',
			[],
			'2.5.5',
		);
	}

}
