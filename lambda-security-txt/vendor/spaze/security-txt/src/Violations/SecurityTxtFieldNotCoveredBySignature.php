<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtFieldNotCoveredBySignature extends SecurityTxtSpecViolation
{

	public function __construct(string $fieldName)
	{
		parent::__construct(
			func_get_args(),
			'The %s field is outside the signed part of the file and is not covered by the signature',
			[$fieldName],
			'draft-foudil-securitytxt-01',
			null,
			'Move the %s field to the signed part of the file and sign the file again',
			[$fieldName],
			'4',
			['2.3'],
		);
	}

}
