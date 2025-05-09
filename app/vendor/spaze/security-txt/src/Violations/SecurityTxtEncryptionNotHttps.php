<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtEncryptionNotHttps extends SecurityTxtFieldUriNotHttps
{

	public function __construct(string $uri)
	{
		parent::__construct(SecurityTxtField::Encryption, $uri, '2.5.4');
	}

}
