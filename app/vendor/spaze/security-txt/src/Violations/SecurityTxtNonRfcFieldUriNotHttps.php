<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

/**
 * Base class to be used for fields that are in the IANA registry but are not part of the security.txt RFC.
 */
abstract class SecurityTxtNonRfcFieldUriNotHttps extends SecurityTxtSpecViolation
{

	/**
	 * @param list<mixed> $constructorParams
	 * @param SecurityTxtField $field
	 * @param string $uri
	 * @param string $specUrl
	 */
	public function __construct(array $constructorParams, SecurityTxtField $field, string $uri, string $specUrl)
	{
		parent::__construct(
			$constructorParams,
			'If the %s field indicates a web URI, then it must begin with "https://"',
			[$field->value],
			null,
			preg_replace('~^http://~i', 'https://', $uri),
			'Make sure the %s field points to an https:// URI',
			[$field->value],
			null,
			specUrl: $specUrl,
		);
	}

}
