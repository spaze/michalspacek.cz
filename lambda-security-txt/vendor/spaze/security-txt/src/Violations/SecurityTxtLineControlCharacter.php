<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtLineControlCharacter extends SecurityTxtSpecViolation
{

	/**
	 * The C0 controls except NUL, which has a violation of its own, the tab, and the CR and LF that end a line, then DEL, which RFC 5198 says should be avoided too, and the
	 * C1 controls, which it says must not appear
	 */
	public const string CONTROL_CHARACTERS = '/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]|\xC2[\x80-\x9F]/';


	public function __construct(string $line)
	{
		$correctValue = trim(preg_replace(self::CONTROL_CHARACTERS, '', $line) ?? '');
		parent::__construct(
			func_get_args(),
			'The line (%s) contains a control character',
			[$line],
			'draft-foudil-securitytxt-00',
			$correctValue !== '' ? $correctValue : null,
			'Remove the control characters from the line',
			[],
			'4',
		);
	}

}
