<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtMultiplePreferredLanguages extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			'The %s field must not appear more than once',
			['Preferred-Languages'],
			'draft-foudil-securitytxt-05',
			null,
			'Make sure the %s field is present only once in the file',
			['Preferred-Languages'],
			'2.5.8',
		);
	}

}
