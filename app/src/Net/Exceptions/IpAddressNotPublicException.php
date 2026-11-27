<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Net\Exceptions;

use Exception;

final class IpAddressNotPublicException extends Exception
{

	/**
	 * @param string|null $rangeType What kind of an address it is instead, null when it is not an IP address at all
	 */
	public function __construct(string $ipAddress, ?string $rangeType)
	{
		parent::__construct($rangeType === null ? "{$ipAddress} is not an IP address" : "{$ipAddress} is not a public IP address ({$rangeType})");
	}

}
