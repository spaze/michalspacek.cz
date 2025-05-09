<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtNoContact extends SecurityTxtSpecViolation
{

	public function __construct()
	{
		parent::__construct(
			func_get_args(),
			'The `Contact` field must always be present',
			[],
			'draft-foudil-securitytxt-00',
			null,
			'Add at least one `Contact` field with a value that follows the URI syntax described in RFC 3986. This means that "mailto" and "tel" URI schemes must be used when specifying email addresses and telephone numbers, e.g. `mailto:security@example.com`',
			[],
			'2.5.3',
			['2.5.4'],
		);
	}

}
