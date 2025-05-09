<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use DateTimeInterface;
use Spaze\SecurityTxt\Fields\SecurityTxtExpires;

final class SecurityTxtExpiresOldFormat extends SecurityTxtSpecViolation
{

	public function __construct(DateTimeInterface $expires)
	{
		$correctValue = $expires->format(SecurityTxtExpires::FORMAT);
		parent::__construct(
			func_get_args(),
			"The value of the `Expires` field follows the format defined in section 3.3 of RFC 5322 but it should be formatted according to the Internet profile of ISO 8601 as defined in RFC 3339",
			[],
			'draft-foudil-securitytxt-12',
			$correctValue,
			'Change the value of the `Expires` field to `%s`',
			[$correctValue],
			'2.5.5',
		);
	}

}
