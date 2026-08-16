<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\SecurityTxtContentType;

final class SecurityTxtContentTypeInvalid extends SecurityTxtSpecViolation
{

	public function __construct(string $uri, ?string $contentType)
	{
		if ($contentType !== null) {
			$format = 'The file at %s has a %s of %s but it should be a %s of %s with the %s parameter set to %s';
			$values = [$uri, 'Content-Type', $contentType, 'Content-Type', SecurityTxtContentType::CONTENT_TYPE, 'charset', SecurityTxtContentType::CHARSET_PARAMETER];
		} else {
			$format = 'The file at %s has no %s but it should be a %s of %s with the %s parameter set to %s';
			$values = [$uri, 'Content-Type', 'Content-Type', SecurityTxtContentType::CONTENT_TYPE, 'charset', SecurityTxtContentType::CHARSET_PARAMETER];
		}
		parent::__construct(
			func_get_args(),
			$format,
			$values,
			'draft-foudil-securitytxt-03',
			SecurityTxtContentType::MEDIA_TYPE,
			'Send a correct %s header value of %s with the %s parameter set to %s',
			['Content-Type', SecurityTxtContentType::CONTENT_TYPE, 'charset', SecurityTxtContentType::CHARSET_PARAMETER],
			'3',
		);
	}

}
