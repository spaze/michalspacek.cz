<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtContactNotUri extends SecurityTxtFieldNotUri
{

	public function __construct(string $uri)
	{
		if (filter_var($uri, FILTER_VALIDATE_EMAIL) !== false) {
			$correctValue = "mailto:{$uri}";
			$howToFix = 'The value looks like an email address, add the "mailto" schema';
		} elseif (preg_match('/^\+?[0-9\-. ]+$/', $uri) === 1) {
			$correctValue = "tel:{$uri}";
			$howToFix = 'The value looks like a phone number, add the "tel" schema';
		} else {
			$correctValue = "https://{$uri}";
			$howToFix = 'The value looks like a hostname, add the "https" schema';
		}
		parent::__construct(SecurityTxtField::Contact, $uri, 'draft-foudil-securitytxt-03', $correctValue, $howToFix, '2.5.3');
	}

}
