<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtHiringNotUri extends SecurityTxtFieldNotUri
{

	public function __construct(string $uri)
	{
		parent::__construct(SecurityTxtField::Hiring, $uri, 'draft-foudil-securitytxt-03', null, null, '2.5.6');
	}

}
