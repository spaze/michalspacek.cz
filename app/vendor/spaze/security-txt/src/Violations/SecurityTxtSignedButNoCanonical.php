<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtSignedButNoCanonical extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			'When digital signatures are used, it is also recommended that organizations use the %s field',
			[SecurityTxtField::Canonical->value],
			'draft-foudil-securitytxt-05',
			null,
			'Add %s field pointing where the %s file is located',
			[SecurityTxtField::Canonical->value, 'security.txt'],
			'2.3',
			['2.5.2'],
		);
	}

}
