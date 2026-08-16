<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtCsafNotHttps extends SecurityTxtNonRfcFieldUriNotHttps
{

	public function __construct(string $uri)
	{
		parent::__construct(
			func_get_args(),
			SecurityTxtField::Csaf,
			$uri,
			'https://docs.oasis-open.org/csaf/csaf/v2.0/os/csaf-v2.0-os.html#718-requirement-8-securitytxt',
		);
	}

}
