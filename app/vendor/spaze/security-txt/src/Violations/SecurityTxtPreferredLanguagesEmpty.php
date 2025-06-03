<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtPreferredLanguagesEmpty extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			'The %s field must have at least one language listed',
			['Preferred-Languages'],
			'draft-foudil-securitytxt-05',
			null,
			'Add one or more languages to the field, separated by commas',
			[],
			'2.5.8',
		);
	}

}
