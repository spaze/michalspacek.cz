<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\DnsLookup;

use Override;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostNotFoundException;
use Uri\WhatWg\Url;

final class SecurityTxtPhpDnsProvider implements SecurityTxtDnsProvider
{

	/**
	 * @throws SecurityTxtHostNotFoundException
	 */
	#[Override]
	public function getRecords(Url $url, string $host): SecurityTxtDnsRecords
	{
		$records = @dns_get_record($host, DNS_A | DNS_AAAA); // intentionally silenced, converted to exception
		if ($records === false) {
			throw new SecurityTxtHostNotFoundException($url->toUnicodeString(), $host);
		}
		$ipRecord = $ipv6Record = null;
		foreach ($records as $record) {
			if ($ipRecord === null && isset($record['ip']) && is_string($record['ip'])) {
				$ipRecord = $record['ip'];
			}
			if ($ipv6Record === null && isset($record['ipv6']) && is_string($record['ipv6'])) {
				$ipv6Record = $record['ipv6'];
			}
			if ($ipRecord !== null && $ipv6Record !== null) {
				break;
			}
		}
		return new SecurityTxtDnsRecords($ipRecord, $ipv6Record);
	}

}
