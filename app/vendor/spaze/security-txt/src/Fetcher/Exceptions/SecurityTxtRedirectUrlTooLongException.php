<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\Fetcher\SecurityTxtRedirects;
use Throwable;
use Uri\WhatWg\Url;

final class SecurityTxtRedirectUrlTooLongException extends SecurityTxtFetcherException
{

	public function __construct(Url $url, SecurityTxtRedirects $redirects, int $maxLength, ?Throwable $previous = null)
	{
		parent::__construct(
			[$url, $redirects, $maxLength],
			"Can't read %s, it redirects to a URL longer than %s characters" . $this->getRedirectsFormat($redirects),
			[$url, (string)$maxLength, ...$redirects->getMessageValues()],
			$url,
			$redirects,
			previous: $previous,
		);
	}

}
