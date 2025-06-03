<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtPossibelFieldTypo extends SecurityTxtSpecViolation
{

	public function __construct(string $fieldName, string $suggestion, string $line)
	{
		$suggestedField = SecurityTxtField::from($suggestion);
		parent::__construct(
			func_get_args(),
			'Field %s may be a typo, did you mean %s?',
			[$fieldName, $suggestedField->value],
			null,
			str_replace($fieldName, $suggestedField->value, $line),
			"Change %s to %s",
			[$fieldName, $suggestedField->value],
			null,
		);
	}

}
