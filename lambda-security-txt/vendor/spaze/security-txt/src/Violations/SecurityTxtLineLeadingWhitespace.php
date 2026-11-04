<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtLineLeadingWhitespace extends SecurityTxtSpecViolation
{

	/**
	 * The Unicode spaces but the ASCII one, U+00A0, U+1680, U+2000 to U+200A, U+202F, U+205F and U+3000, then the zero width space U+200B and the zero width no-break
	 * space U+FEFF, as UTF-8 bytes, which look like whitespace, but RFC 9116, where whitespace is a space or a tab, reads as a part of what follows them
	 */
	public const string UNICODE_SPACES = '\xC2\xA0|\xE1\x9A\x80|\xE2\x80[\x80-\x8B\xAF]|\xE2\x81\x9F|\xE3\x80\x80|\xEF\xBB\xBF';


	public function __construct(string $line)
	{
		parent::__construct(
			func_get_args(),
			'The line (%s) starts with whitespace',
			[$line],
			'draft-foudil-securitytxt-00',
			preg_replace('/^(?:\s|' . self::UNICODE_SPACES . ')++/', '', $line) ?? ltrim($line),
			'Remove the whitespace from the start of the line',
			[],
			'4',
			['2.1'],
		);
	}

}
