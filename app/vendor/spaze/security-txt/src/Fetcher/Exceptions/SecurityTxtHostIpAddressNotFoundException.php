<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;

final class SecurityTxtHostIpAddressNotFoundException extends SecurityTxtFetcherException
{

	public function __construct(string $url, string $host, ?Throwable $previous = null)
	{
		parent::__construct([$url, $host], "Can't open %s, no IP address for %s found", [$url, $host], $url, previous: $previous);
	}

}
