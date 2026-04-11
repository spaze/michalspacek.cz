<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\DnsLookup;

use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostNotFoundException;
use Uri\WhatWg\Url;

interface SecurityTxtDnsProvider
{

	/**
	 * @throws SecurityTxtHostNotFoundException
	 */
	public function getRecords(Url $url, string $host): SecurityTxtDnsRecords;

}
