<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtSignatureKeyExpired extends SecurityTxtSpecViolation
{

	public function __construct(string $keyFingerprint)
	{
		parent::__construct(
			func_get_args(),
			'The file is digitally signed using an OpenPGP cleartext signature but the keyring the signature was verified with says the key %s has expired',
			[$keyFingerprint],
			'draft-foudil-securitytxt-01',
			null,
			"Extend the key's expiration date and publish the updated public key, or sign the file with a key that hasn't expired",
			[],
			'2.3',
		);
	}

}
