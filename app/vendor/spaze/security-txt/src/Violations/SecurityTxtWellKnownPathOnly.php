<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtWellKnownPathOnly extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			"%s not found at the top-level path",
			['security.txt'],
			'draft-foudil-securitytxt-02',
			null,
			'Redirect the top-level file to the one under the %s path',
			['/.well-known/'],
			'3',
		);
	}

}
