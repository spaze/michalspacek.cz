<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\SecurityTxt;

final class SecurityTxtContentTypeInvalid extends SecurityTxtSpecViolation
{

	public function __construct(string $url, ?string $contentType)
	{
		if ($contentType !== null) {
			$format = 'The file at %s has a %s of %s but it should be a %s of %s with the %s parameter set to %s';
			$values = [$url, 'Content-Type', $contentType, 'Content-Type', SecurityTxt::CONTENT_TYPE, 'charset', SecurityTxt::CHARSET];
		} else {
			$format = 'The file at %s has no %s but it should be a %s of %s with the %s parameter set to %s';
			$values = [$url, 'Content-Type', 'Content-Type', SecurityTxt::CONTENT_TYPE, 'charset', SecurityTxt::CHARSET];
		}
		parent::__construct(
			func_get_args(),
			$format,
			$values,
			'draft-foudil-securitytxt-03',
			SecurityTxt::CONTENT_TYPE_HEADER,
			'Send a correct %s header value of %s with the %s parameter set to %s',
			['Content-Type', SecurityTxt::CONTENT_TYPE, 'charset', SecurityTxt::CHARSET],
			'3',
		);
	}

}
