<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtMultipleBugBounty extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			'The %s field must not appear more than once',
			[SecurityTxtField::BugBounty->value],
			null,
			null,
			'Make sure the %s field is present only once in the file',
			[SecurityTxtField::BugBounty->value],
			null,
			specUrl: 'https://www.iana.org/assignments/security-txt-fields/security-txt-fields.xhtml#security-txt-fields',
		);
	}

}
