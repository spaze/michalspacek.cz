<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtFileLocationNotUri extends SecurityTxtSpecViolation
{

	public function __construct(string $uri)
	{
		parent::__construct(
			func_get_args(),
			"The location of the file %s doesn't follow the URI syntax described in RFC 3986, the scheme is missing",
			[$uri],
			'draft-foudil-securitytxt-00',
			null,
			'Use a URI as the value',
			[],
			'3',
		);
	}

}
