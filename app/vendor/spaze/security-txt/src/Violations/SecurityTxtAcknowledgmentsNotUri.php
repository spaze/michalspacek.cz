<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtAcknowledgmentsNotUri extends SecurityTxtFieldNotUri
{

	public function __construct(string $uri)
	{
		parent::__construct(SecurityTxtField::Acknowledgments, $uri, 'draft-foudil-securitytxt-03', null, null, '2.5.1');
	}

}
