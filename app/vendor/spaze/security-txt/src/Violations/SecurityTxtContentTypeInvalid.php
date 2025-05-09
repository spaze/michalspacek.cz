<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\SecurityTxt;

final class SecurityTxtContentTypeInvalid extends SecurityTxtSpecViolation
{

	public function __construct(string $url, ?string $contentType)
	{
		if ($contentType !== null) {
			$format = 'The file at `%s` has a `Content-Type` of `%s` but it should be a `Content-Type` of `%s` with the `charset` parameter set to `%s`';
			$values = [$url, $contentType, SecurityTxt::CONTENT_TYPE, SecurityTxt::CHARSET];
		} else {
			$format = 'The file at `%s` has no `Content-Type` but it should be a `Content-Type` of `%s` with the `charset` parameter set to `%s`';
			$values = [$url, SecurityTxt::CONTENT_TYPE, SecurityTxt::CHARSET];
		}
		parent::__construct(
			func_get_args(),
			$format,
			$values,
			'draft-foudil-securitytxt-03',
			SecurityTxt::CONTENT_TYPE_HEADER,
			sprintf('Send a correct `Content-Type` header value of `%s` with the `charset` parameter set to `%s`', SecurityTxt::CONTENT_TYPE, SecurityTxt::CHARSET),
			[],
			'3',
		);
	}

}
