<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\SecurityTxtContentType;

final class SecurityTxtContentNotUtf8 extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		$utf8 = strtoupper(SecurityTxtContentType::CHARSET);
		parent::__construct(
			func_get_args(),
			'The file content is not encoded in %s',
			[$utf8],
			'draft-foudil-securitytxt-00',
			null,
			'Re-encode the file in %s',
			[$utf8],
			'4',
		);
	}

}
