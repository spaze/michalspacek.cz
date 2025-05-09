<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtTopLevelPathOnly extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			"`security.txt` wasn't found under the `/.well-known/` path",
			[],
			'draft-foudil-securitytxt-02',
			null,
			'Move the `security.txt` file from the top-level location under the `/.well-known/` path and redirect `/security.txt` to `/.well-known/security.txt`',
			[],
			'3',
		);
	}

}
