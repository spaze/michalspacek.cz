<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use DateTimeImmutable;
use Spaze\SecurityTxt\Fields\SecurityTxtExpires;

final class SecurityTxtExpiresSoon extends SecurityTxtSpecViolation
{

	public function __construct(int $inDays)
	{
		if ($inDays > 0) {
			$format = 'The file will be considered stale in %s days';
			$values = [(string)$inDays];
		} else {
			$format = 'The file will be considered stale later today';
			$values = [];
		}
		parent::__construct(
			func_get_args(),
			$format,
			$values,
			'draft-foudil-securitytxt-10',
			new DateTimeImmutable('+1 year midnight -1 sec')->format(SecurityTxtExpires::FORMAT),
			'Update the value of the %s field',
			['Expires'],
			'2.5.5',
		);
	}

}
