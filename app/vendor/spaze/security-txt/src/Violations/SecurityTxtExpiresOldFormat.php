<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtExpiresOldFormat extends SecurityTxtSpecViolation
{

	public function __construct(string $correctValue)
	{
		parent::__construct(
			func_get_args(),
			"The value of the %s field follows the format defined in section 3.3 of RFC 5322 but it should be formatted according to the Internet profile of ISO 8601 as defined in RFC 3339",
			['Expires'],
			'draft-foudil-securitytxt-12',
			$correctValue,
			'Change the value of the %s field to %s',
			['Expires', $correctValue],
			'2.5.5',
		);
	}

}
