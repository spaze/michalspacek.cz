<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\SecurityTxtContentType;

final class SecurityTxtByteOrderMark extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			"The file starts with a byte order mark (BOM), which RFC 5198 doesn't allow",
			[],
			'draft-foudil-securitytxt-03',
			null,
			'Save the file as %s without the byte order mark',
			[strtoupper(SecurityTxtContentType::CHARSET)],
			'4',
		);
	}

}
