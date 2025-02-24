<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Http;

use DateTimeInterface;

final readonly class Cookie
{

	public function __construct(
		private string $name,
		private string $value,
		private string|int|DateTimeInterface|null $expire,
		private string $path,
		private string $domain,
		private bool $secure,
	) {
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function getValue(): string
	{
		return $this->value;
	}


	public function getExpire(): DateTimeInterface|int|string|null
	{
		return $this->expire;
	}


	public function getPath(): string
	{
		return $this->path;
	}


	public function getDomain(): string
	{
		return $this->domain;
	}


	public function isSecure(): bool
	{
		return $this->secure;
	}

}
