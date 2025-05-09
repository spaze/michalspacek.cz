<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtPolicyNotHttps extends SecurityTxtFieldUriNotHttps
{

	public function __construct(string $uri)
	{
		parent::__construct(SecurityTxtField::Policy, $uri, '2.5.7');
	}

}
