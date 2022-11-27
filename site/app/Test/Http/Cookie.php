<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Http;

use DateTimeInterface;

class Cookie
{

	public function __construct(
		private readonly string $name,
		private readonly string $value,
		private readonly string|int|DateTimeInterface|null $expire,
		private readonly string $path,
		private readonly string $domain,
		private readonly bool $secure,
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
