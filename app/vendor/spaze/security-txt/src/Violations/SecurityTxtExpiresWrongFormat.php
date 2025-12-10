<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use DateTimeImmutable;
use Spaze\SecurityTxt\Fields\SecurityTxtExpires;
use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtExpiresWrongFormat extends SecurityTxtSpecViolation
{

	public function __construct(?string $correctValue = null)
	{
		if ($correctValue === null) {
			$correctValue = (new DateTimeImmutable('+1 year midnight -1 sec'))->format(SecurityTxtExpires::FORMAT);
		}
		parent::__construct(
			func_get_args(),
			'The format of the value of the %s field is wrong',
			[SecurityTxtField::Expires->value],
			'draft-foudil-securitytxt-09',
			$correctValue,
			'The %s field should contain a date and time in the future formatted according to the Internet profile of ISO 8601 as defined in RFC 3339',
			[SecurityTxtField::Expires->value],
			'2.5.5',
		);
	}

}
