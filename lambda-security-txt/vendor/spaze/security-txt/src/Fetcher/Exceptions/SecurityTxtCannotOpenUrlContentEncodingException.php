<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\Fetcher\SecurityTxtIpAddressType;
use Spaze\SecurityTxt\Fetcher\SecurityTxtRedirects;
use Throwable;
use Uri\WhatWg\Url;

final class SecurityTxtCannotOpenUrlContentEncodingException extends SecurityTxtFetcherException
{

	public function __construct(
		Url $url,
		SecurityTxtRedirects $redirects,
		private readonly string $ipAddress,
		private readonly SecurityTxtIpAddressType $ipAddressType,
		string $contentEncoding,
		?Throwable $previous = null,
	) {
		$format = "Can't read %s" . $this->getRedirectsFormat($redirects) . match ($ipAddressType) {
			SecurityTxtIpAddressType::V4 => ' using its IPv4 address %s',
			SecurityTxtIpAddressType::V6 => ' using its IPv6 address %s',
		} . ", the %s header says %s, which the body can't be decoded with";
		parent::__construct(
			[$url, $redirects, $ipAddress, $ipAddressType->value, $contentEncoding],
			$format,
			[$url, ...$redirects->getMessageValues(), $ipAddress, 'Content-Encoding', $contentEncoding],
			$url,
			$redirects,
			previous: $previous,
		);
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
