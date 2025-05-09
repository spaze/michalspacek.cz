<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtCanonicalNotHttps extends SecurityTxtFieldUriNotHttps
{

	public function __construct(string $uri)
	{
		parent::__construct(SecurityTxtField::Canonical, $uri, '2.5.2');
	}

}
