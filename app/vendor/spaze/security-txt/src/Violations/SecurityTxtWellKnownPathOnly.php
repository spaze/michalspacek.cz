<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtWellKnownPathOnly extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			"`security.txt` not found at the top-level path",
			[],
			'draft-foudil-securitytxt-02',
			null,
			'Redirect the top-level file to the one under the `/.well-known/` path',
			[],
			'3',
		);
	}

}
