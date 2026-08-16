<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtSignatureCannotVerify extends SecurityTxtSpecViolation
{

	public function __construct(string $message, string $code, string $source, string $libraryMessage)
	{
		parent::__construct(
			func_get_args(),
			"The file is digitally signed using an OpenPGP cleartext signature but the signature is damaged and cannot be verified (%s, %s, %s, %s)",
			[$message, $code, $source, $libraryMessage],
			'draft-foudil-securitytxt-01',
			null,
			'Sign the file again',
			[],
			'2.3',
		);
	}

}
