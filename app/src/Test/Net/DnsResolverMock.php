<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Net;

use MichalSpacekCz\Net\DnsRecord;
use MichalSpacekCz\Net\DnsResolver;
use Override;

final class DnsResolverMock implements DnsResolver
{

	/** @var array<string, list<DnsRecord>> */
	private array $records = [];


	/**
	 * @param list<DnsRecord> $records
	 */
	public function setRecords(string $hostname, array $records): void
	{
		$this->records[$hostname] = $records;
	}


	/**
	 * @return list<DnsRecord>
	 */
	#[Override]
	public function getRecords(string $hostname, int $type): array
	{
		return $this->records[$hostname] ?? [];
	}

}
