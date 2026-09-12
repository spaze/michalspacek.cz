<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressInvalidException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressNotPublicException;
use Spaze\SecurityTxt\SecurityTxtHost;
use Uri\WhatWg\Url;

final class SecurityTxtIpAddressValidator
{

	/**
	 * RFC 6052 NAT64 well-known prefix, /96; the embedded IPv4 is the last 32 bits.
	 */
	private const string NAT64_WELL_KNOWN_PREFIX = '64:ff9b::';

	/**
	 * RFC 8215 NAT64 local-use prefix, /48.
	 */
	private const string NAT64_LOCAL_USE_PREFIX = '64:ff9b:1::';


	/**
	 * @throws SecurityTxtHostIpAddressInvalidException
	 * @throws SecurityTxtHostIpAddressNotPublicException
	 */
	public function validate(string $ipAddress, SecurityTxtIpAddressType $type, SecurityTxtHost $host, Url $url): void
	{
		// A `match` for the same reason `SecurityTxtHostIpAddressInvalidException` uses one, and with more riding on it: a case added later would fall through a ternary to
		// `FILTER_FLAG_IPV6` while the `V6` test below stayed false, so it would be filtered as IPv6 and skip the NAT64 check that keeps a mapped address off the metadata endpoint
		$flag = match ($type) {
			SecurityTxtIpAddressType::V4 => FILTER_FLAG_IPV4,
			SecurityTxtIpAddressType::V6 => FILTER_FLAG_IPV6,
		};
		if (filter_var($ipAddress, FILTER_VALIDATE_IP, $flag) === false) {
			throw new SecurityTxtHostIpAddressInvalidException($host, $ipAddress, $type, $url);
		}
		if (filter_var($ipAddress, FILTER_VALIDATE_IP, $flag | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_GLOBAL_RANGE) === false) {
			throw new SecurityTxtHostIpAddressNotPublicException($host, $ipAddress, $url);
		}
		if ($type === SecurityTxtIpAddressType::V6) {
			$this->validateNat64IpAddress($ipAddress, $host, $url);
		}
	}


	/**
	 * A NAT64 address embeds an IPv4 address the range check above can't see, so 64:ff9b::a9fe:a9fe (embedding
	 * 169.254.169.254) would pass as a public IPv6 and a NAT64 gateway would forward the request to that internal host.
	 * For the well-known prefix the embedded IPv4 is the last 32 bits, so re-check just that against the same public-range
	 * filter: a mapped internal target is rejected while a mapped public one still works, which is how an IPv6-only host
	 * with DNS64/NAT64 reaches an IPv4-only site. The local-use prefix has no fixed embedding position and is non-global
	 * by definition, so reject it whole.
	 *
	 * @throws SecurityTxtHostIpAddressNotPublicException
	 */
	private function validateNat64IpAddress(string $ipAddress, SecurityTxtHost $host, Url $url): void
	{
		$binary = inet_pton($ipAddress);
		if ($binary === false) {
			return;
		}
		$localUse = inet_pton(self::NAT64_LOCAL_USE_PREFIX);
		if ($localUse !== false && substr($binary, 0, 6) === substr($localUse, 0, 6)) {
			throw new SecurityTxtHostIpAddressNotPublicException($host, $ipAddress, $url);
		}
		$wellKnown = inet_pton(self::NAT64_WELL_KNOWN_PREFIX);
		if ($wellKnown !== false && substr($binary, 0, 12) === substr($wellKnown, 0, 12)) {
			$embedded = inet_ntop(substr($binary, 12, 4));
			if (
				$embedded === false
				|| filter_var($embedded, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_GLOBAL_RANGE) === false
			) {
				throw new SecurityTxtHostIpAddressNotPublicException($host, $ipAddress, $url);
			}
		}
	}

}
