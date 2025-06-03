<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtSignatureExtensionNotLoaded extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			'The %s extension is not available, cannot verify or create signatures',
			['gnupg'],
			'draft-foudil-securitytxt-01',
			null,
			'Load the %s extension',
			['gnupg'],
			'2.3',
		);
	}

}
