<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtTopLevelPathOnly extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			"%s wasn't found under the %s path",
			['security.txt', '/.well-known/'],
			'draft-foudil-securitytxt-02',
			null,
			'Move the %s file from the top-level location under the %s path and redirect %s to %s',
			['security.txt', '/.well-known/', '/security.txt', '/.well-known/security.txt'],
			'3',
		);
	}

}
