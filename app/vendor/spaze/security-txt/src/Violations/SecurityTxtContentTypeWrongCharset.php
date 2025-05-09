<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\SecurityTxt;

final class SecurityTxtContentTypeWrongCharset extends SecurityTxtSpecViolation
{

	public function __construct(string $url, string $contentType, ?string $charset)
	{
		$format = $charset !== null
			? 'The file at `%s` has a correct `Content-Type` of `%s` but the `%s` parameter should be changed to `%s`'
			: 'The file at `%s` has a correct `Content-Type` of `%s` but the `%s` parameter is missing';
		parent::__construct(
			func_get_args(),
			$format,
			$charset !== null ? [$url, $contentType, $charset, SecurityTxt::CHARSET] : [$url, $contentType, SecurityTxt::CHARSET],
			'draft-foudil-securitytxt-03',
			SecurityTxt::CONTENT_TYPE_HEADER,
			sprintf($charset !== null ? 'Change the parameter to `%s`' : 'Add a `%s` parameter', SecurityTxt::CHARSET),
			[],
			'3',
		);
	}

}
