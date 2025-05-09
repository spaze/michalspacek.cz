<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;

final class SecurityTxtHostIpAddressInvalidTypeException extends SecurityTxtFetcherException
{

	public function __construct(string $host, string $type, string $url, ?Throwable $previous = null)
	{
		parent::__construct([$host, $type, $url], "IP address of `%s` is a %s, should be a string", [$host, $type], $url, previous: $previous);
	}

}
