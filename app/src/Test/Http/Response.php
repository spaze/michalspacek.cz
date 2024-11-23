<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Http;

use DateTimeInterface;
use Nette\Http\IResponse;
use Override;

class Response implements IResponse
{

	private int $code = IResponse::S200_OK;

	private ?string $reason = null;

	/** @var array<string, string> */
	private array $headers = [];

	/** @var array<string, array<int, string>> */
	private array $allHeaders = [];

	/** @var array<string, list<Cookie>> */
	private array $cookies = [];

	public string $cookieDomain = '';

	public string $cookiePath = '/';

	public bool $cookieSecure = false;

	private string $contentType = '';

	private ?string $contentCharset = null;

	private string $redirectTo = '';

	private int $redirectCode = IResponse::S302_Found;

	private ?string $expiration = null;

	private bool $isSent = false;


	#[Override]
	public function setCode(int $code, ?string $reason = null): self
	{
		$this->code = $code;
		$this->reason = $reason;
		return $this;
	}


	#[Override]
	public function getCode(): int
	{
		return $this->code;
	}


	public function getReason(): ?string
	{
		return $this->reason;
	}


	#[Override]
	public function setHeader(string $name, string $value): self
	{
		$name = strtolower($name);
		$this->headers[$name] = $value;
		$this->allHeaders[$name] = [$value];
		return $this;
	}


	#[Override]
	public function addHeader(string $name, string $value): self
	{
		$name = strtolower($name);
		$this->headers[$name] = $value;
		$this->allHeaders[$name][] = $value;
		return $this;
	}


	#[Override]
	public function setContentType(string $type, ?string $charset = null): self
	{
		$this->contentType = $type;
		$this->contentCharset = $charset;
		return $this;
	}


	#[Override]
	public function redirect(string $url, int $code = self::S302_Found): void
	{
		$this->redirectTo = $url;
		$this->redirectCode = $code;
	}


	#[Override]
	public function setExpiration(?string $expire): self
	{
		$this->expiration = $expire;
		return $this;
	}


	#[Override]
	public function isSent(): bool
	{
		return $this->isSent;
	}


	#[Override]
	public function getHeader(string $header): ?string
	{
		$header = strtolower($header);
		return $this->headers[$header] ?? null;
	}


	/**
	 * @return array<string, string>
	 */
	#[Override]
	public function getHeaders(): array
	{
		return $this->headers;
	}


	public function deleteHeader(string $name): self
	{
		$name = strtolower($name);
		unset($this->headers[$name], $this->allHeaders[$name]);
		return $this;
	}


	#[Override]
	public function setCookie(
		string $name,
		string $value,
		string|int|DateTimeInterface|null $expire,
		?string $path = null,
		?string $domain = null,
		?bool $secure = null,
		?bool $httpOnly = null,
		?string $sameSite = null,
	): self {
		$this->cookies[$name][] = new Cookie(
			$name,
			$value,
			$expire,
			$path ?? $this->cookiePath,
			$domain ?? $this->cookieDomain,
			$secure ?? $this->cookieSecure,
		);
		return $this;
	}


	#[Override]
	public function deleteCookie(string $name, ?string $path = null, ?string $domain = null, ?bool $secure = null): void
	{
		unset($this->cookies[$name]);
	}


	/**
	 * @return list<Cookie>
	 */
	public function getCookie(string $name): array
	{
		return $this->cookies[$name] ?? [];
	}


	/**
	 * @return array<string, array<int, string>>
	 */
	public function getAllHeaders(): array
	{
		return $this->allHeaders;
	}


	public function getContentType(): string
	{
		return $this->contentType;
	}


	public function getContentCharset(): ?string
	{
		return $this->contentCharset;
	}


	public function getRedirectTo(): string
	{
		return $this->redirectTo;
	}


	public function getRedirectCode(): int
	{
		return $this->redirectCode;
	}


	public function getExpiration(): ?string
	{
		return $this->expiration;
	}


	public function sent(bool $isSent): void
	{
		$this->isSent = $isSent;
	}


	public function reset(): void
	{
		$this->headers = [];
		$this->allHeaders = [];
		$this->cookies = [];
	}

}
