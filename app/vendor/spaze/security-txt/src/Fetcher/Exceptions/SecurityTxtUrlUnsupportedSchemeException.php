<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\Fetcher\SecurityTxtRedirects;
use Throwable;
use Uri\WhatWg\Url;

final class SecurityTxtUrlUnsupportedSchemeException extends SecurityTxtFetcherException
{

	public function __construct(Url $url, SecurityTxtRedirects $redirects, ?Throwable $previous = null)
	{
		parent::__construct(
			[$url, $redirects],
			'URL %s has an unsupported scheme' . $this->getRedirectsFormat($redirects),
			[$url, ...$redirects->getMessageValues()],
			$url,
			$redirects,
			previous: $previous,
		);
	}

}
