<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtMultipleExpires extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			'The %s field must not appear more than once',
			[SecurityTxtField::Expires->value],
			'draft-foudil-securitytxt-09',
			null,
			'Make sure the %s field is present only once in the file',
			[SecurityTxtField::Expires->value],
			'2.5.5',
		);
	}

}
