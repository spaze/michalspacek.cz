<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

abstract class SecurityTxtFieldUriNotHttps extends SecurityTxtSpecViolation
{

	/**
	 * @param list<mixed> $constructorParams
	 * @param SecurityTxtField $field
	 * @param string $uri
	 * @param string $specSection
	 */
	public function __construct(array $constructorParams, SecurityTxtField $field, string $uri, string $specSection)
	{
		parent::__construct(
			$constructorParams,
			'If the %s field indicates a web URI, then it must begin with "https://"',
			[$field->value],
			'draft-foudil-securitytxt-06',
			preg_replace('~^http://~i', 'https://', $uri),
			'Make sure the %s field points to an https:// URI',
			[$field->value],
			$specSection,
		);
	}

}
