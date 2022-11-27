<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Http;

use Nette\Http\IResponse;

class Response implements IResponse
{

	private int $code;

	/** @var array<string, string> */
	private array $headers;

	/** @var array<string, array<int, string>> */
	private array $allHeaders;

	/** @var array<string, Cookie> */
	private array $cookies;

	public string $cookieDomain = '';

	public string $cookiePath = '/';

	public bool $cookieSecure = false;

	private string $contentType;

	private ?string $contentCharset;

	private string $redirectTo;

	private int $redirectCode;

	private ?string $expiration;

	private bool $isSent;


	public function setCode(int $code, string $reason = null): self
	{
		$this->code = $code;
		return $this;
	}


	public function getCode(): int
	{
		return $this->code;
	}


	public function setHeader(string $name, string $value): self
	{
		$name = strtolower($name);
		$this->headers[$name] = $value;
		$this->allHeaders[$name] = [$value];
		return $this;
	}


	public function addHeader(string $name, string $value): self
	{
		$name = strtolower($name);
		$this->headers[$name] = $value;
		$this->allHeaders[$name][] = $value;
		return $this;
	}


	public function setContentType(string $type, string $charset = null): self
	{
		$this->contentType = $type;
		$this->contentCharset = $charset;
		return $this;
	}


	public function redirect(string $url, int $code = self::S302_FOUND): void
	{
		$this->redirectTo = $url;
		$this->redirectCode = $code;
	}


	public function setExpiration(?string $expire): self
	{
		$this->expiration = $expire;
		return $this;
	}


	public function isSent(): bool
	{
		return $this->isSent;
	}


	public function getHeader(string $header): ?string
	{
		$header = strtolower($header);
		return $this->headers[$header] ?? null;
	}


	/**
	 * @return array<string, string>
	 */
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


	public function setCookie(string $name, string $value, $expire, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = null, string $sameSite = null): self
	{
		$this->cookies[$name] = new Cookie(
			$name,
			$value,
			$expire,
			$path ?? $this->cookiePath,
			$domain ?? $this->cookieDomain,
			$secure ?? $this->cookieSecure,
		);
		return $this;
	}


	public function deleteCookie(string $name, string $path = null, string $domain = null, bool $secure = null): void
	{
		unset($this->cookies[$name]);
	}


	public function getCookie(string $name): ?Cookie
	{
		return $this->cookies[$name] ?? null;
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

}
