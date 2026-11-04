<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\Fetcher\SecurityTxtRedirects;
use Spaze\SecurityTxt\SecurityTxtHost;
use Throwable;
use Uri\WhatWg\Url;

final class SecurityTxtHostIpAddressNotFoundException extends SecurityTxtFetcherException
{

	public function __construct(Url $url, SecurityTxtHost $host, ?SecurityTxtRedirects $redirects = null, ?Throwable $previous = null)
	{
		$redirects ??= new SecurityTxtRedirects();
		parent::__construct(
			$this->withRedirectsParam([$url, $host], $redirects),
			"Can't open %s" . $this->getRedirectsFormat($redirects) . ", no IP address for %s found",
			[$url, ...$redirects->getMessageValues(), $host],
			$url,
			$redirects,
			previous: $previous,
		);
	}

}
