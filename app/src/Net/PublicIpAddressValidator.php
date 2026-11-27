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
	 * The address has to be public for both ip-lib and PHP. Each has a list of its own and refuses something the other one takes as public, so neither check is spare.
	 * Inside 2001::/23 ip-lib goes by the IANA registry, which has 2001:1::1 and 2001:1::2 as globally reachable, though what answers them is the nearest PCP or TURN
	 * device on the sender's own network (RFC 7723, RFC 8155). PHP refuses those with the rest of 2001::/23, and takes multicast and NAT64 addresses as public.
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
