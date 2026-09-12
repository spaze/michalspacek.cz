<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\SecurityTxtHost;
use Throwable;
use Uri\WhatWg\Url;

final class SecurityTxtHostNotFoundException extends SecurityTxtFetcherException
{

	public function __construct(Url $url, SecurityTxtHost $host, ?Throwable $previous = null)
	{
		parent::__construct([$url, $host], "Can't open %s, can't resolve %s", [$url, $host], $url, previous: $previous);
	}

}
