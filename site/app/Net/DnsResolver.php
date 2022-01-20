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
		$records = @dns_get_record($hostname, $type);  // intentionally @, warning converted to exception
		if (!$records) {
			throw new DnsGetRecordException(Helpers::getLastError());
		}
		$result = [];
		foreach ($records as $record) {
			$result[] = new DnsRecord(...$record);
		}
		return $result;
	}

}
