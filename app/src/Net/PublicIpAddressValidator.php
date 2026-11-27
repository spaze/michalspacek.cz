<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Net;

use IPLib\Factory;
use IPLib\Range\Type;
use MichalSpacekCz\Net\Exceptions\IpAddressNotPublicException;

final class PublicIpAddressValidator
{

	/**
	 * For an address from somewhere else, like a DNS answer: whoever controls the answer picks where a request to the address goes.
	 * Only a public address passes, so the request can't go to a host on an internal network, to this host or to a cloud metadata service.
	 *
	 * The address has to be public for both ip-lib and PHP: ip-lib takes some special-purpose IPv6 ranges as public, like the one for documentation,
	 * see https://github.com/mlocati/ip-lib/issues/128. The check by PHP can go once ip-lib refuses them.
	 *
	 * Returns the address as ip-lib writes it, and that is what to connect to, not what came in. ip-lib reads 0177.0.0.1 as the public 177.0.0.1,
	 * while a resolver reads the leading zero as octal, which makes it 127.0.0.1.
	 *
	 * @psalm-taint-escape ssrf
	 * @psalm-taint-escape file
	 * @throws IpAddressNotPublicException
	 */
	public function validate(string $ipAddress): string
	{
		$address = Factory::parseAddressString($ipAddress);
		if ($address === null) {
			throw new IpAddressNotPublicException($ipAddress, null);
		}
		$rangeType = $address->getRangeType();
		if ($rangeType !== Type::T_PUBLIC) {
			throw new IpAddressNotPublicException($ipAddress, Type::getName($rangeType));
		}
		$checked = $address->toString();
		if (filter_var($checked, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) === false) {
			throw new IpAddressNotPublicException($ipAddress, 'Not globally reachable');
		}
		return $checked;
	}

}
