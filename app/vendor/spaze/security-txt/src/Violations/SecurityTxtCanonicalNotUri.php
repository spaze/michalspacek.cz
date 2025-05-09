<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtCanonicalNotUri extends SecurityTxtFieldNotUri
{

	public function __construct(string $uri)
	{
		parent::__construct(SecurityTxtField::Canonical, $uri, 'draft-foudil-securitytxt-05', null, null, '2.5.2');
	}

}
