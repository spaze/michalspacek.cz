<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Signature\SecurityTxtSignatureErrorInfo;

final class SecurityTxtSignatureCannotVerify extends SecurityTxtSpecViolation
{

	public function __construct(SecurityTxtSignatureErrorInfo $errorInfo)
	{
		parent::__construct(
			func_get_args(),
			"The file is digitally signed using an OpenPGP cleartext signature but the signature is damaged and cannot be verified (%s, %s, %s, %s)",
			[$errorInfo->getMessageAsString(), $errorInfo->getCodeAsString(), $errorInfo->getSourceAsString(), $errorInfo->getLibraryMessageAsString()],
			'draft-foudil-securitytxt-01',
			null,
			'Sign the file again',
			[],
			'2.3',
		);
	}

}
