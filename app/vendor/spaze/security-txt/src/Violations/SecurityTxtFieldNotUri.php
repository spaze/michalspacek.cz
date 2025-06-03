<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

abstract class SecurityTxtFieldNotUri extends SecurityTxtSpecViolation
{

	public function __construct(
		SecurityTxtField $field,
		string $uri,
		string $since,
		?string $correctValue,
		?string $howToFix,
		?string $specSection,
	) {
		parent::__construct(
			func_get_args(),
			"The %s value (%s) doesn't follow the URI syntax described in RFC 3986, the scheme is missing",
			[$field->value, $uri],
			$since,
			$correctValue,
			$howToFix ?? 'Use a URI as the value',
			[],
			$specSection,
		);
	}

}
