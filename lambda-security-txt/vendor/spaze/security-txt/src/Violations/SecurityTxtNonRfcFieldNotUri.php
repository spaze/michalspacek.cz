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
	 * @param SecurityTxtNotUriReason $reason
	 * @param string|null $correctValue
	 * @param string|null $character The one the reason names, if it names one
	 * @param string $specUrl
	 */
	public function __construct(
		array $constructorParams,
		SecurityTxtField $field,
		string $uri,
		SecurityTxtNotUriReason $reason,
		?string $correctValue,
		?string $character,
		string $specUrl,
	) {
		parent::__construct(
			$constructorParams,
			'The %s value (%s) ' . $reason->getFormat(),
			[$field->value, $uri, ...$reason->getValues($character)],
			null,
			self::asUrl($correctValue),
			$reason->getHowToFix(),
			$reason->getHowToFixValues($character),
			null,
			specUrl: $specUrl,
		);
	}

}
