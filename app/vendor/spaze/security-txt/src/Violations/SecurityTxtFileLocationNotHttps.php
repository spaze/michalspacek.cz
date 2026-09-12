<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtFileLocationNotHttps extends SecurityTxtSpecViolation
{

	public function __construct(string $uri)
	{
		parent::__construct(
			func_get_args(),
			"The file at %s must use HTTPS",
			[self::asUrl($uri)],
			'draft-foudil-securitytxt-06',
			self::asUrl(preg_replace('~^http://~i', 'https://', $uri)),
			'Use HTTPS to serve the %s file',
			['security.txt'],
			'3',
		);
	}

}
