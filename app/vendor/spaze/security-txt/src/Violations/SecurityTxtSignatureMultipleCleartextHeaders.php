<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtSignatureMultipleCleartextHeaders extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			'The file must contain only one %s header',
			['-----BEGIN PGP SIGNED MESSAGE-----'],
			'draft-foudil-securitytxt-01',
			null,
			'Sign the whole file at once and remove the extra %s headers',
			['-----BEGIN PGP SIGNED MESSAGE-----'],
			'4',
			['2.3'],
		);
	}

}
