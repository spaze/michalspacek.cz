<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Http;

final readonly class Cookie
{

	public function __construct(
		private string $name,
		private string $value,
		private ?int $expire,
		private string $path,
		private string $domain,
		private bool $secure,
		private ?string $sameSite,
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


	public function getExpire(): ?int
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


	public function getSameSite(): ?string
	{
		return $this->sameSite;
	}

}
