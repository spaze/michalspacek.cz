<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtUnknownField extends SecurityTxtSpecViolation
{

	public function __construct(string $fieldName, string $line)
	{
		parent::__construct(
			func_get_args(),
			'Field %s is unknown',
			[$fieldName],
			null,
			"# {$line}",
			"Remove the line or comment it out",
			[],
			null,
		);
	}

}
