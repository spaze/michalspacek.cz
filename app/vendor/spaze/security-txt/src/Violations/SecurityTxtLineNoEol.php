<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtLineNoEol extends SecurityTxtSpecViolation
{

	public function __construct(string $line)
	{
		parent::__construct(
			func_get_args(),
			"The line (`%s`) doesn't end with neither `<CRLF>` nor `<LF>`",
			[$line],
			'draft-foudil-securitytxt-03',
			$line . '`<LF>`',
			"End the line with either `<CRLF>` or `<LF>`",
			[],
			'2.2',
			['4'],
		);
	}

}
