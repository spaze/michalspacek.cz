<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Uri\WhatWg\Url;

final class SecurityTxtRedirectNotHttps extends SecurityTxtSpecViolation
{

	public function __construct(Url $url, Url $redirect)
	{
		parent::__construct(
			func_get_args(),
			'The redirects from %s go through %s, which does not use HTTPS',
			[$url, $redirect],
			'draft-foudil-securitytxt-06',
			null,
			'Make every redirect go to an %s URL, a redirect sent over plain HTTP can be changed to point anywhere',
			['https://'],
			'3',
		);
	}

}
