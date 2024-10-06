<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Net;

readonly class DnsRecord
{

	public function __construct(
		private string $host,
		private string $class,
		private int $ttl,
		private string $type,
		private ?string $ip = null,
		private ?string $ipv6 = null,
	) {
	}


	public function getHost(): string
	{
		return $this->host;
	}


	public function getClass(): string
	{
		return $this->class;
	}


	public function getTtl(): int
	{
		return $this->ttl;
	}


	public function getType(): string
	{
		return $this->type;
	}


	public function getIp(): ?string
	{
		return $this->ip;
	}


	public function getIpv6(): ?string
	{
		return $this->ipv6;
	}

}
