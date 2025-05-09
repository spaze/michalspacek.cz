<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtSchemeNotHttps extends SecurityTxtSpecViolation
{

	public function __construct(string $url)
	{
		parent::__construct(
			func_get_args(),
			"The file at `%s` must use HTTPS",
			[$url],
			'draft-foudil-securitytxt-06',
			preg_replace('~^http://~', 'https://', $url),
			'Use HTTPS to serve the `security.txt` file',
			[],
			'3',
		);
	}

}
