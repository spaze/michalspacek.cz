<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\Fetcher\SecurityTxtIpAddressType;
use Throwable;

final class SecurityTxtHostIpAddressInvalidException extends SecurityTxtFetcherException
{

	/**
	 * @param value-of<SecurityTxtIpAddressType> $ipAddressType
	 */
	public function __construct(string $host, string $ip, int $ipAddressType, string $url, ?Throwable $previous = null)
	{
		if ($ipAddressType === SecurityTxtIpAddressType::V4->value) {
			$type = 'IPv4';
		} else {
			$type = 'IPv6';
		}
		parent::__construct(
			[$host, $ip, $ipAddressType, $url],
			"Host %s resolves to an invalid %s address %s",
			[$host, $type, $ip],
			$url,
			previous: $previous,
		);
	}

}
