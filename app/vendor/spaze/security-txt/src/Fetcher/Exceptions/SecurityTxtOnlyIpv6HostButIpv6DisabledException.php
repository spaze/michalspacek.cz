<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;

final class SecurityTxtOnlyIpv6HostButIpv6DisabledException extends SecurityTxtFetcherException
{

	public function __construct(string $host, string $ipv6, string $url, ?Throwable $previous = null)
	{
		parent::__construct([$host, $ipv6, $url], "Only IPv6 host is available (`%s`, `%s`) but IPv6 is disabled", [$host, $ipv6], $url, previous: $previous);
	}

}
