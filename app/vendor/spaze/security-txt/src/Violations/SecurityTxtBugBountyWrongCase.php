<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtBugBountyWrongCase extends SecurityTxtSpecViolation
{

	public function __construct(string $value)
	{
		parent::__construct(
			func_get_args(),
			'The first letter of the %s field value %s should be uppercase',
			[SecurityTxtField::BugBounty->value, $value],
			null,
			ucfirst($value),
			'Change the first letter of the value %s to uppercase',
			[$value],
			null,
			specUrl: 'https://www.iana.org/assignments/security-txt-fields/security-txt-fields.xhtml#security-txt-fields',
		);
	}

}
