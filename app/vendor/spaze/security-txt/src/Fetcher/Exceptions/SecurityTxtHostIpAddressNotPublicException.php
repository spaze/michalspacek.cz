<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\Fetcher\SecurityTxtRedirects;
use Spaze\SecurityTxt\SecurityTxtHost;
use Throwable;
use Uri\WhatWg\Url;

final class SecurityTxtHostIpAddressNotPublicException extends SecurityTxtFetcherException
{

	public function __construct(SecurityTxtHost $host, string $ip, Url $url, ?SecurityTxtRedirects $redirects = null, ?Throwable $previous = null)
	{
		$redirects ??= new SecurityTxtRedirects();
		parent::__construct(
			$this->withRedirectsParam([$host, $ip, $url], $redirects),
			"Host %s resolves to a non-public IP address %s" . $this->getRedirectsFormat($redirects),
			[$host, $ip, ...$redirects->getMessageValues()],
			$url,
			$redirects,
			previous: $previous,
		);
	}

}
