<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\DnsLookup;

use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressInvalidTypeException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostNotFoundException;

interface SecurityTxtDnsProvider
{

	/**
	 * @throws SecurityTxtHostNotFoundException
	 * @throws SecurityTxtHostIpAddressInvalidTypeException
	 */
	public function getRecords(string $url, string $host): SecurityTxtDnsRecords;

}
