<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtSignatureExpired extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			'The file is digitally signed using an OpenPGP cleartext signature but the signature has expired',
			[],
			'draft-foudil-securitytxt-01',
			null,
			"Sign the file again, with a signature that doesn't expire before the date in the %s field",
			[SecurityTxtField::Expires->value],
			'2.3',
		);
	}

}
