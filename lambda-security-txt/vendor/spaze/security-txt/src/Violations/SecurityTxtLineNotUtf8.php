<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\SecurityTxtContentType;

final class SecurityTxtLineNotUtf8 extends SecurityTxtSpecViolation
{

	/**
	 * @param list<string> $bytes Each piece of the line that isn't UTF-8
	 */
	public function __construct(string $line, array $bytes)
	{
		$utf8 = strtoupper(SecurityTxtContentType::CHARSET);
		$pieces = $this->getRepeatedFormat('%s', count($bytes));
		$one = count($bytes) === 1;
		parent::__construct(
			func_get_args(),
			"The line (%s) contains {$pieces}, " . ($one ? "which isn't" : "which aren't") . ' valid %s',
			[$line, ...$bytes, $utf8],
			'draft-foudil-securitytxt-00',
			null,
			"Replace {$pieces} with " . ($one ? 'the character it was' : 'the characters they were') . ' meant to be, encoded in %s',
			[...$bytes, $utf8],
			'4',
		);
	}

}
