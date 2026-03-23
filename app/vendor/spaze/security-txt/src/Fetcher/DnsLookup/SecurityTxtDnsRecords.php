<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\DnsLookup;

final readonly class SecurityTxtDnsRecords
{

	public function __construct(
		private ?string $ipRecord,
		private ?string $ipv6Record,
	) {
	}


	public function getIpRecord(): ?string
	{
		return $this->ipRecord;
	}


	public function getIpv6Record(): ?string
	{
		return $this->ipv6Record;
	}

}
