<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Net;

use IPLib\Address\IPv4;
use IPLib\Address\IPv6;
use MichalSpacekCz\Database\TypedDatabase;

final class IpRanges
{

	public function __construct(
		private readonly TypedDatabase $typedDatabase,
	) {
	}


	public function getRangeName(string $ip, IpAddressType $type): ?string
	{
		$address = $type === IpAddressType::V6 ? IPv6::parseString($ip) : IPv4::parseString($ip);
		if ($address === null) {
			return null;
		}
		return $this->typedDatabase->fetchFieldStringNullable(
			'SELECT s.`source`
				FROM ip_ranges r
				JOIN ip_ranges_sources s ON r.key_source = s.id
				WHERE ? BETWEEN range_start AND range_end AND range_type = ?',
			$address->getComparableString(),
			$address->getAddressType(),
		);
	}

}
