<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtEncryptionNotUri extends SecurityTxtFieldNotUri
{

	public function __construct(string $uri)
	{
		parent::__construct(SecurityTxtField::Encryption, $uri, 'draft-foudil-securitytxt-00', null, null, '2.5.4');
	}

}
