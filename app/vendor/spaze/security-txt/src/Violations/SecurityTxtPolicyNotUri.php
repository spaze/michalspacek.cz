<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtPolicyNotUri extends SecurityTxtFieldNotUri
{

	public function __construct(string $uri)
	{
		parent::__construct(SecurityTxtField::Policy, $uri, 'draft-foudil-securitytxt-02', null, null, '2.5.7');
	}

}
