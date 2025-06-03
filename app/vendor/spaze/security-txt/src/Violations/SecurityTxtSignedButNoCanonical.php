<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtSignedButNoCanonical extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			'When digital signatures are used, it is also recommended that organizations use the %s field',
			['Canonical'],
			'draft-foudil-securitytxt-05',
			null,
			'Add %s field pointing where the %s file is located',
			['Canonical', 'security.txt'],
			'2.3',
			['2.5.2'],
		);
	}

}
