<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\Fetcher\SecurityTxtRedirects;
use Spaze\SecurityTxt\SecurityTxtHost;
use Throwable;
use Uri\WhatWg\Url;

final class SecurityTxtOnlyIpv6HostButIpv6DisabledException extends SecurityTxtFetcherException
{

	public function __construct(SecurityTxtHost $host, string $ipv6, Url $url, ?SecurityTxtRedirects $redirects = null, ?Throwable $previous = null)
	{
		$redirects ??= new SecurityTxtRedirects();
		parent::__construct(
			$this->withRedirectsParam([$host, $ipv6, $url], $redirects),
			'Only IPv6 host is available (%s, %s) but IPv6 is disabled' . $this->getRedirectsFormat($redirects),
			[$host, $ipv6, ...$redirects->getMessageValues()],
			$url,
			$redirects,
			previous: $previous,
		);
	}

}
