<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtContactNotUri extends SecurityTxtFieldNotUri
{

	public function __construct(string $uri, SecurityTxtNotUriReason $reason = SecurityTxtNotUriReason::Unspecified, ?string $correctValue = null, ?string $character = null)
	{
		parent::__construct(func_get_args(), SecurityTxtField::Contact, $uri, $reason, $correctValue, $character, 'draft-foudil-securitytxt-03', '2.5.3');
	}

}
