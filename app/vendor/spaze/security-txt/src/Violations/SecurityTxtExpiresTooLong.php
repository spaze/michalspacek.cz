<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use DateTimeImmutable;
use Spaze\SecurityTxt\Fields\SecurityTxtExpires;
use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtExpiresTooLong extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		$correctValue = (new DateTimeImmutable('+1 year midnight -1 sec'))->format(SecurityTxtExpires::FORMAT);
		parent::__construct(
			func_get_args(),
			'The value of the %s field should be less than a year into the future to avoid staleness',
			[SecurityTxtField::Expires->value],
			'draft-foudil-securitytxt-10',
			$correctValue,
			'Change the value of the %s field to less than a year into the future',
			[SecurityTxtField::Expires->value],
			'2.5.5',
		);
	}

}
