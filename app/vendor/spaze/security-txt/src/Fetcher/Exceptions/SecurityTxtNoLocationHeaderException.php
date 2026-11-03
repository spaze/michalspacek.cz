<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\Fetcher\SecurityTxtRedirects;
use Throwable;
use Uri\WhatWg\Url;

final class SecurityTxtNoLocationHeaderException extends SecurityTxtFetcherException
{

	public function __construct(Url $url, int $httpCode, ?SecurityTxtRedirects $redirects = null, ?Throwable $previous = null)
	{
		$redirects ??= new SecurityTxtRedirects();
		parent::__construct(
			$this->withRedirectsParam([$url, $httpCode], $redirects),
			'HTTP response with code %s is missing a %s header when fetching %s' . $this->getRedirectsFormat($redirects),
			[(string)$httpCode, 'Location', $url, ...$redirects->getMessageValues()],
			$url,
			$redirects,
			previous: $previous,
		);
	}

}
