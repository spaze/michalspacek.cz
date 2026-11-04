<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtFieldWrongSpaceAfterColon extends SecurityTxtSpecViolation
{

	public function __construct(string $fieldName, string $line)
	{
		parent::__construct(
			func_get_args(),
			'The line (%s) should have one space between the colon and the value',
			[$line],
			'draft-foudil-securitytxt-00',
			"{$fieldName}: " . (preg_replace('/^(?:\s|' . SecurityTxtLineLeadingWhitespace::UNICODE_SPACES . ')++/', '', explode(':', $line, 2)[1] ?? '') ?? ltrim(explode(':', $line, 2)[1] ?? '')),
			'Put one space between the colon and the value',
			[],
			'4',
		);
	}

}
