<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtSignedFileMalformed extends SecurityTxtSpecViolation
{

	public function __construct(string $line)
	{
		parent::__construct(
			func_get_args(),
			'The file is not signed as one whole message, the signed file breaks at the line (%s), so the signature is not verified',
			[$line],
			'draft-foudil-securitytxt-05',
			null,
			'Sign the whole file again, for example with %s, and publish it unchanged',
			['gpg --clearsign'],
			'4',
			['2.3'],
		);
	}

}
