<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtLineNulByte extends SecurityTxtSpecViolation
{

	public function __construct(string $line)
	{
		$correctValue = trim(str_replace("\0", '', $line));
		parent::__construct(
			func_get_args(),
			'The line (%s) contains a NUL byte',
			[$line],
			'draft-foudil-securitytxt-00',
			$correctValue !== '' ? $correctValue : null,
			'Remove the NUL bytes from the line',
			[],
			'4',
		);
	}

}
