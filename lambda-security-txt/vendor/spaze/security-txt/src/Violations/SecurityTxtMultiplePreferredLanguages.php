<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtMultiplePreferredLanguages extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			'The %s field must not appear more than once',
			[SecurityTxtField::PreferredLanguages->value],
			'draft-foudil-securitytxt-05',
			null,
			'Make sure the %s field is present only once in the file',
			[SecurityTxtField::PreferredLanguages->value],
			'2.5.8',
		);
	}

}
