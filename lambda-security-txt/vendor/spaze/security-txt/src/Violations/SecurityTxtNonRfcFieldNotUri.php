<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

/**
 * Base class to be used for fields that are in the IANA registry but are not part of the security.txt RFC.
 */
abstract class SecurityTxtNonRfcFieldNotUri extends SecurityTxtSpecViolation
{

	/**
	 * @param list<mixed> $constructorParams
	 * @param SecurityTxtField $field
	 * @param string $uri
	 * @param string|null $correctValue
	 * @param string|null $howToFix
	 * @param string $specUrl
	 */
	public function __construct(
		array $constructorParams,
		SecurityTxtField $field,
		string $uri,
		?string $correctValue,
		?string $howToFix,
		string $specUrl,
	) {
		parent::__construct(
			$constructorParams,
			"The %s value (%s) doesn't follow the URI syntax described in RFC 3986, the scheme is missing",
			[$field->value, $uri],
			null,
			$correctValue,
			$howToFix ?? 'Use a URI as the value',
			[],
			null,
			specUrl: $specUrl,
		);
	}

}
