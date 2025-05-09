<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtHiringNotHttps extends SecurityTxtFieldUriNotHttps
{

	public function __construct(string $uri)
	{
		parent::__construct(SecurityTxtField::Hiring, $uri, '2.5.6');
	}

}
