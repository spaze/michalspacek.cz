<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;

final class SecurityTxtHostIpAddressNotPublicException extends SecurityTxtFetcherException
{

	public function __construct(string $host, string $ip, string $url, ?Throwable $previous = null)
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
