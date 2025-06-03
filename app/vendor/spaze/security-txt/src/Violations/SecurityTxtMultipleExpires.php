<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtMultipleExpires extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			'The %s field must not appear more than once',
			['Expires'],
			'draft-foudil-securitytxt-09',
			null,
			'Make sure the %s field is present only once in the file',
			['Expires'],
			'2.5.5',
		);
	}

}
