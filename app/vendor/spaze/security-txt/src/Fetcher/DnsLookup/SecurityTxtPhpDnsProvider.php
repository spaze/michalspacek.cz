<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\DnsLookup;

use Override;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressInvalidTypeException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostNotFoundException;

final class SecurityTxtPhpDnsProvider implements SecurityTxtDnsProvider
{

	/**
	 * @throws SecurityTxtHostNotFoundException
	 * @throws SecurityTxtHostIpAddressInvalidTypeException
	 */
	#[Override]
	public function getRecords(string $url, string $host): SecurityTxtDnsRecords
	{
		$records = @dns_get_record($host, DNS_A | DNS_AAAA); // intentionally silenced, converted to exception
		if ($records === false) {
			throw new SecurityTxtHostNotFoundException($url, $host);
		}
		$records = array_merge(...$records);
		$ipRecord = $records['ip'] ?? null;
		$ipv6Record = $records['ipv6'] ?? null;
		if ($ipRecord !== null && !is_string($ipRecord)) {
			throw new SecurityTxtHostIpAddressInvalidTypeException($host, get_debug_type($ipRecord), $url);
		}
		if ($ipv6Record !== null && !is_string($ipv6Record)) {
			throw new SecurityTxtHostIpAddressInvalidTypeException($host, get_debug_type($ipv6Record), $url);
		}
		return new SecurityTxtDnsRecords($ipRecord, $ipv6Record);
	}

}
