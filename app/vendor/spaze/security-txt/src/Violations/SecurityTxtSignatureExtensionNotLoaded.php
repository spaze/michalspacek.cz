<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtSignatureExtensionNotLoaded extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			'The `gnupg` extension is not available, cannot verify or create signatures',
			[],
			'draft-foudil-securitytxt-01',
			null,
			'Load the `gnupg` extension',
			[],
			'2.3',
		);
	}

}
