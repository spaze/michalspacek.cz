<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\SecurityTxtHost;
use Throwable;
use Uri\WhatWg\Url;

final class SecurityTxtHostIpAddressNotPublicException extends SecurityTxtFetcherException
{

	public function __construct(SecurityTxtHost $host, string $ip, Url $url, ?Throwable $previous = null)
	{
		parent::__construct(
			[$host, $ip, $url],
			"Host %s resolves to a non-public IP address %s",
			[$host, $ip],
			$url,
			previous: $previous,
		);
	}

}
