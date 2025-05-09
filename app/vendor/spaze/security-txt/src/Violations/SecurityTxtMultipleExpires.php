<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtMultipleExpires extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			'The `Expires` field must not appear more than once',
			[],
			'draft-foudil-securitytxt-09',
			null,
			'Make sure the `Expires` field is present only once in the file',
			[],
			'2.5.5',
		);
	}

}
