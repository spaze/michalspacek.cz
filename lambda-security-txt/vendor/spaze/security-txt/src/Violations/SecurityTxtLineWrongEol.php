<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtLineWrongEol extends SecurityTxtSpecViolation
{

	public function __construct(string $line)
	{
		$correctValue = trim($line);
		parent::__construct(
			func_get_args(),
			'The line (%s) ends with whitespace other than spaces and tabs',
			[$line],
			'draft-foudil-securitytxt-00',
			$correctValue !== '' ? $correctValue : null,
			'Remove the whitespace from the end of the line',
			[],
			'4',
			['2.2'],
		);
	}

}
