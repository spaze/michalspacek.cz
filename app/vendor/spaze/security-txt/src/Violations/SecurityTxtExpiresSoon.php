<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use DateTimeImmutable;
use Spaze\SecurityTxt\Fields\SecurityTxtExpires;
use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtExpiresSoon extends SecurityTxtSpecViolation
{

	public function __construct(int $inDays)
	{
		if ($inDays > 0) {
			$format = 'The file will be considered stale in %s ' . ($inDays === 1 ? 'day' : 'days');
			$values = [(string)$inDays];
		} else {
			$format = 'The file will be considered stale in less than a day';
			$values = [];
		}
		parent::__construct(
			func_get_args(),
			$format,
			$values,
			'draft-foudil-securitytxt-10',
			(new DateTimeImmutable('+1 year midnight -1 sec'))->format(SecurityTxtExpires::FORMAT),
			'Update the value of the %s field',
			[SecurityTxtField::Expires->value],
			'2.5.5',
		);
	}

}
