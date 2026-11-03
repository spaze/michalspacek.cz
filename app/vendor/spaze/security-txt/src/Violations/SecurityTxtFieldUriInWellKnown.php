<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtFieldUriInWellKnown extends SecurityTxtSpecViolation
{

	public function __construct(SecurityTxtField $field, string $uri)
	{
		parent::__construct(
			func_get_args(),
			'The %s value (%s) points under the %s path, where only the names registered with IANA can be used',
			[$field->value, self::asUrl($uri), '/.well-known/'],
			'draft-foudil-securitytxt-10',
			null,
			'Move it out of the %s path, unless the name it uses there is in the Well-Known URIs registry at IANA',
			['/.well-known/'],
			'6',
		);
	}

}
