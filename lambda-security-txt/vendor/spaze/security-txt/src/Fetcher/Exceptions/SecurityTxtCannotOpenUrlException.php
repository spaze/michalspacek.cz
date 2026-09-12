<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\Fetcher\SecurityTxtIpAddressType;
use Spaze\SecurityTxt\Fetcher\SecurityTxtRedirects;
use Throwable;
use Uri\WhatWg\Url;

final class SecurityTxtCannotOpenUrlException extends SecurityTxtFetcherException
{

	/**
	 * @param string|null $error Must not contain anything the checked host controls; `curl_strerror()` is safe, `curl_error()` is not because it quotes strings like the certificate subject name. It reaches `getMessage()`, which encodes it down to printable ASCII, and `getMessageValues()` and the serialized `params`, which do not, so a consumer logging or rendering either one sees whatever was put here
	 */
	public function __construct(
		Url $url,
		SecurityTxtRedirects $redirects,
		private readonly ?string $ipAddress = null,
		private readonly ?SecurityTxtIpAddressType $ipAddressType = null,
		?string $error = null,
		?Throwable $previous = null,
	) {
		$format = "Can't open %s" . $this->getRedirectsFormat($redirects);
		$values = [$url, ...$redirects->getMessageValues()];
		if ($this->ipAddress !== null) {
			$format .= match ($this->ipAddressType) {
				SecurityTxtIpAddressType::V4 => ' using its IPv4 address %s',
				SecurityTxtIpAddressType::V6 => ' using its IPv6 address %s',
				null => ' using its IP address %s',
			};
			$values[] = $this->ipAddress;
		}
		if ($error !== null) {
			$format .= ' (%s)';
			$values[] = $error;
		}
		parent::__construct(
			[$url, $redirects, $ipAddress, $this->ipAddressType?->value, $error],
			$format,
			$values,
			$url,
			$redirects,
			previous: $previous,
		);
	}


	public function getIpAddress(): ?string
	{
		return $this->ipAddress;
	}


	public function getIpAddressType(): ?SecurityTxtIpAddressType
	{
		return $this->ipAddressType;
	}

}
