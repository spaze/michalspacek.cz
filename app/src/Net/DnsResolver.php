<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Net;

use MichalSpacekCz\Net\Exceptions\DnsGetRecordException;

interface DnsResolver
{

	/**
	 * @param string $hostname
	 * @param int $type One of DNS_* types or a bit mask of more types (e.g. DNS_A | DNS_AAAA)
	 * @return array<int, DnsRecord>
	 * @throws DnsGetRecordException
	 */
	public function getRecords(string $hostname, int $type): array;

}
