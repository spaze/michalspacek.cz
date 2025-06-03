<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\SecurityTxt;

final class SecurityTxtContentTypeWrongCharset extends SecurityTxtSpecViolation
{

	public function __construct(string $url, string $contentType, ?string $charset)
	{
		$format = $charset !== null
			? 'The file at %s has a correct %s of %s but the %s parameter should be changed to %s'
			: 'The file at %s has a correct %s of %s but the %s parameter is missing';
		parent::__construct(
			func_get_args(),
			$format,
			$charset !== null ? [$url, 'Content-Type', $contentType, $charset, SecurityTxt::CHARSET] : [$url, 'Content-Type', $contentType, SecurityTxt::CHARSET],
			'draft-foudil-securitytxt-03',
			SecurityTxt::CONTENT_TYPE_HEADER,
			$charset !== null ? 'Change the parameter to %s' : 'Add a %s parameter',
			[SecurityTxt::CHARSET],
			'3',
		);
	}

}
