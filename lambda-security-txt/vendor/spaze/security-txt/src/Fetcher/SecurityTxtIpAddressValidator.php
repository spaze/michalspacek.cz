<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressInvalidException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressNotPublicException;

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
	public function validate(string $ipAddress, SecurityTxtIpAddressType $type, string $host, string $url): void
	{
		$flag = $type === SecurityTxtIpAddressType::V4 ? FILTER_FLAG_IPV4 : FILTER_FLAG_IPV6;
		if (filter_var($ipAddress, FILTER_VALIDATE_IP, $flag) === false) {
			throw new SecurityTxtHostIpAddressInvalidException($host, $ipAddress, $type->value, $url);
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
	private function validateNat64IpAddress(string $ipAddress, string $host, string $url): void
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
