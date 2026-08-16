<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtLineNoEol extends SecurityTxtSpecViolation
{

	public function __construct(string $line)
	{
		parent::__construct(
			func_get_args(),
			"The line (%s) doesn't end with neither %s nor %s",
			[$line, '<CRLF>', '<LF>'],
			'draft-foudil-securitytxt-03',
			$line . '<LF>',
			"End the line with either %s or %s",
			['<CRLF>', '<LF>'],
			'2.2',
			['4'],
		);
	}

}
