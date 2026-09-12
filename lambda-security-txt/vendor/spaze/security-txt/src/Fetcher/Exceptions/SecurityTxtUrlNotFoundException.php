<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\Fetcher\SecurityTxtIpAddressType;
use Throwable;
use Uri\WhatWg\Url;

final class SecurityTxtUrlNotFoundException extends SecurityTxtFetcherException
{

	public function __construct(
		Url $url,
		int $code,
		private readonly string $ipAddress,
		private readonly SecurityTxtIpAddressType $ipAddressType,
		?Throwable $previous = null,
	) {
		parent::__construct([$url, $code, $ipAddress, $ipAddressType->value], 'URL %s not found, code %s', [$url, (string)$code], $url, code: $code, previous: $previous);
	}


	public function getIpAddress(): string
	{
		return $this->ipAddress;
	}


	public function getIpAddressType(): SecurityTxtIpAddressType
	{
		return $this->ipAddressType;
	}

}
