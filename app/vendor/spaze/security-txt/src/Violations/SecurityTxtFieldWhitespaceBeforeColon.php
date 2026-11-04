<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtFieldWhitespaceBeforeColon extends SecurityTxtSpecViolation
{

	public function __construct(string $fieldName, string $line)
	{
		parent::__construct(
			func_get_args(),
			'The line (%s) has whitespace between the field name and the colon',
			[$line],
			'draft-foudil-securitytxt-00',
			"{$fieldName}: " . (preg_replace('/^(?:\s|' . SecurityTxtLineLeadingWhitespace::UNICODE_SPACES . ')++/', '', explode(':', $line, 2)[1] ?? '') ?? ltrim(explode(':', $line, 2)[1] ?? '')),
			'Remove the whitespace before the colon',
			[],
			'4',
		);
	}

}
