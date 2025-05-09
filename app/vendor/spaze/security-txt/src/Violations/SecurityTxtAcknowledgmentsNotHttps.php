<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtAcknowledgmentsNotHttps extends SecurityTxtFieldUriNotHttps
{

	public function __construct(string $uri)
	{
		parent::__construct(SecurityTxtField::Acknowledgments, $uri, '2.5.1');
	}

}
