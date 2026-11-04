<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtSignatureKeyRevoked extends SecurityTxtSpecViolation
{

	public function __construct(string $keyFingerprint)
	{
		parent::__construct(
			func_get_args(),
			'The file is digitally signed using an OpenPGP cleartext signature but the keyring the signature was verified with says the key %s has been revoked, so the signature may have been forged',
			[$keyFingerprint],
			'draft-foudil-securitytxt-01',
			null,
			"Sign the file with a key that hasn't been revoked",
			[],
			'2.3',
		);
	}

}
