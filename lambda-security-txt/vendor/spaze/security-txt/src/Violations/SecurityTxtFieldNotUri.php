<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

abstract class SecurityTxtFieldNotUri extends SecurityTxtSpecViolation
{

	/**
	 * @param list<mixed> $constructorParams
	 * @param SecurityTxtField $field
	 * @param string $uri
	 * @param string $since
	 * @param string|null $correctValue
	 * @param string|null $howToFix
	 * @param string|null $specSection
	 */
	public function __construct(
		array $constructorParams,
		SecurityTxtField $field,
		string $uri,
		string $since,
		?string $correctValue,
		?string $howToFix,
		?string $specSection,
	) {
		parent::__construct(
			$constructorParams,
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
