<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\Fetcher\SecurityTxtIpAddressType;
use Throwable;

final class SecurityTxtUrlNotFoundException extends SecurityTxtFetcherException
{

	/**
	 * @param value-of<SecurityTxtIpAddressType> $ipAddressType
	 */
	public function __construct(
		string $url,
		int $code,
		private readonly string $ipAddress,
		private readonly int $ipAddressType,
		?Throwable $previous = null,
	) {
		parent::__construct([$url, $code, $ipAddress, $ipAddressType], 'URL %s not found, code %s', [$url, (string)$code], $url, code: $code, previous: $previous);
	}


	public function getIpAddress(): string
	{
		return $this->ipAddress;
	}


	public function getIpAddressType(): int
	{
		return $this->ipAddressType;
	}

}
