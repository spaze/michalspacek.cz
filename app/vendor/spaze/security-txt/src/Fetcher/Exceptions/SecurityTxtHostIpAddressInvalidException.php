<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\Fetcher\SecurityTxtIpAddressType;
use Spaze\SecurityTxt\Fetcher\SecurityTxtRedirects;
use Spaze\SecurityTxt\SecurityTxtHost;
use Throwable;
use Uri\WhatWg\Url;

final class SecurityTxtHostIpAddressInvalidException extends SecurityTxtFetcherException
{

	public function __construct(
		SecurityTxtHost $host,
		string $ip,
		private readonly SecurityTxtIpAddressType $ipAddressType,
		Url $url,
		?SecurityTxtRedirects $redirects = null,
		?Throwable $previous = null,
	) {
		$redirects ??= new SecurityTxtRedirects();
		// A `match` rather than an `if`, so a case added later has to be given a name here instead of quietly being called IPv6
		$type = match ($ipAddressType) {
			SecurityTxtIpAddressType::V4 => 'IPv4',
			SecurityTxtIpAddressType::V6 => 'IPv6',
		};
		parent::__construct(
			$this->withRedirectsParam([$host, $ip, $ipAddressType->value, $url], $redirects),
			"Host %s resolves to an invalid %s address %s" . $this->getRedirectsFormat($redirects),
			[$host, $type, $ip, ...$redirects->getMessageValues()],
			$url,
			$redirects,
			previous: $previous,
		);
	}


	public function getIpAddressType(): SecurityTxtIpAddressType
	{
		return $this->ipAddressType;
	}

}
