<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtLineNeitherFieldNorComment extends SecurityTxtSpecViolation
{

	/**
	 * @param SecurityTxtField|null $field The field whose name the line starts with, not followed by a colon
	 */
	public function __construct(string $line, ?SecurityTxtField $field = null)
	{
		if ($field !== null) {
			$value = preg_replace('/^(?:\s|' . SecurityTxtLineLeadingWhitespace::UNICODE_SPACES . ')++/', '', substr($line, strlen($field->value))) ?? ltrim(substr($line, strlen($field->value)));
			$correctValue = $value !== '' ? "{$field->value}: {$value}" : null;
			$howToFix = 'Put a colon after the %s field name';
			$howToFixValues = [$field->value];
		} else {
			$correctValue = "# {$line}";
			$howToFix = 'Remove the line or comment it out';
			$howToFixValues = [];
		}
		parent::__construct(
			func_get_args(),
			'The line (%s) is neither a field nor a comment',
			[$line],
			'draft-foudil-securitytxt-00',
			$correctValue,
			$howToFix,
			$howToFixValues,
			'4',
		);
	}

}
