<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Net;

use MichalSpacekCz\Net\Exceptions\DnsGetRecordException;
use Nette\Utils\Helpers;

class DnsResolver
{

	/**
	 * @param string $hostname
	 * @param int $type One of DNS_* types or a bit mask of more types (e.g. DNS_A | DNS_AAAA)
	 * @return array<int, DnsRecord>
	 * @throws DnsGetRecordException
	 */
	public function getRecords(string $hostname, int $type): array
	{
		$records = @dns_get_record($hostname, $type); // intentionally @, warning converted to exception
		if ($records === false) {
			throw new DnsGetRecordException(Helpers::getLastError());
		}
		$result = [];
		foreach ($records as $record) {
			assert(is_string($record['host']));
			assert(is_string($record['class']));
			assert(is_int($record['ttl']));
			assert(is_string($record['type']));
			assert(is_string($record['ip']));
			$result[] = new DnsRecord($record['host'], $record['class'], $record['ttl'], $record['type'], $record['ip']);
		}
		return $result;
	}

}
