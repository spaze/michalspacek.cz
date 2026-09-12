<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\SecurityTxtHost;
use Throwable;
use Uri\WhatWg\Url;

final class SecurityTxtHostIpAddressNotFoundException extends SecurityTxtFetcherException
{

	public function __construct(Url $url, SecurityTxtHost $host, ?Throwable $previous = null)
	{
		parent::__construct([$url, $host], "Can't open %s, no IP address for %s found", [$url, $host], $url, previous: $previous);
	}

}
