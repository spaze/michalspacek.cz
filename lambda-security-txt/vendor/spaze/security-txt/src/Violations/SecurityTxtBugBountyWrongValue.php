<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtBugBountyWrongValue extends SecurityTxtSpecViolation
{

	public function __construct(string $value)
	{
		parent::__construct(
			func_get_args(),
			'The value of the %s field (%s) should be either %s or %s',
			[SecurityTxtField::BugBounty->value, $value, 'True', 'False'],
			null,
			null,
			'Change the value of the %s field to %s or %s',
			[SecurityTxtField::BugBounty->value, 'True', 'False'],
			null,
			specUrl: 'https://www.iana.org/assignments/security-txt-fields/security-txt-fields.xhtml#security-txt-fields',
		);
	}

}
