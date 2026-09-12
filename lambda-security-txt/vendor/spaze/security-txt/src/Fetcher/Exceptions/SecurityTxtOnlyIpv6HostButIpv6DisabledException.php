<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\SecurityTxtHost;
use Throwable;
use Uri\WhatWg\Url;

final class SecurityTxtOnlyIpv6HostButIpv6DisabledException extends SecurityTxtFetcherException
{

	public function __construct(SecurityTxtHost $host, string $ipv6, Url $url, ?Throwable $previous = null)
	{
		parent::__construct([$host, $ipv6, $url], 'Only IPv6 host is available (%s, %s) but IPv6 is disabled', [$host, $ipv6], $url, previous: $previous);
	}

}
