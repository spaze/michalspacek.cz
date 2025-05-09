<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtSignedButNoCanonical extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			'When digital signatures are used, it is also recommended that organizations use the `Canonical` field',
			[],
			'draft-foudil-securitytxt-05',
			null,
			'Add `Canonical` field pointing where the `security.txt` file is located',
			[],
			'2.3',
			['2.5.2'],
		);
	}

}
