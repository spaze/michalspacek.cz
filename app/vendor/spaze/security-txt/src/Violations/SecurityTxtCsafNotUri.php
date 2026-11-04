<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtCsafNotUri extends SecurityTxtNonRfcFieldNotUri
{

	public function __construct(string $uri, SecurityTxtNotUriReason $reason = SecurityTxtNotUriReason::Unspecified, ?string $correctValue = null, ?string $character = null)
	{
		parent::__construct(
			func_get_args(),
			SecurityTxtField::Csaf,
			$uri,
			$reason,
			$correctValue,
			$character,
			'https://docs.oasis-open.org/csaf/csaf/v2.0/os/csaf-v2.0-os.html#71-requirements',
		);
	}

}
