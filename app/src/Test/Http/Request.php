<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Http;

use Nette\Http\FileUpload;
use Nette\Http\IRequest;
use Nette\Http\UrlImmutable;
use Nette\Http\UrlScript;
use Override;

final class Request implements IRequest
{

	/** @var array<string, mixed> */
	private array $post = [];

	/** @var array<string, mixed> */
	private array $cookies = [];

	/** @var array<string, FileUpload> */
	private array $files = [];

	private string $method = '';

	/** @var array<string, string> */
	private array $headers = [];

	private ?string $remoteAddress = null;

	private ?string $remoteHost = null;

	private ?string $rawBody = null;

	private bool $sameSite = false;

	private UrlScript $url;


	public function __construct()
	{
		$this->url = new UrlScript();
	}


	#[Override]
	public function getUrl(): UrlScript
	{
		return $this->url;
	}


	public function setUrl(UrlScript $url): void
	{
		$this->url = $url;
	}


	#[Override]
	public function getQuery(?string $key = null)
	{
		return $key === null ? $this->url->getQueryParameters() : $this->url->getQueryParameter($key);
	}


	#[Override]
	public function getPost(?string $key = null)
	{
		return $key === null ? $this->post : $this->post[$key] ?? null;
	}


	public function setPost(string $key, mixed $value): void
	{
		$this->post[$key] = $value;
	}


	/**
	 * @param string $key
	 * @return FileUpload|null
	 */
	#[Override]
	public function getFile(string $key): ?FileUpload
	{
		return $this->files[$key] ?? null;
	}


	/**
	 * @return array<string, FileUpload>
	 */
	#[Override]
	public function getFiles(): array
	{
		return $this->files;
	}


	#[Override]
	public function getCookie(string $key): mixed
	{
		return $this->cookies[$key] ?? null;
	}


	/**
	 * @return array<string, mixed>
	 */
	#[Override]
	public function getCookies(): array
	{
		return $this->cookies;
	}


	#[Override]
	public function getMethod(): string
	{
		return $this->method;
	}


	#[Override]
	public function isMethod(string $method): bool
	{
		return strcasecmp($this->method, $method) === 0;
	}


	#[Override]
	public function getHeader(string $header): ?string
	{
		return $this->headers[strtolower($header)] ?? null;
	}


	/**
	 * @return array<string, string>
	 */
	#[Override]
	public function getHeaders(): array
	{
		return $this->headers;
	}


	#[Override]
	public function isSecured(): bool
	{
		return $this->url->getScheme() === 'https';
	}


	#[Override]
	public function isAjax(): bool
	{
		return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
	}


	#[Override]
	public function getRemoteAddress(): ?string
	{
		return $this->remoteAddress;
	}


	#[Override]
	public function getRemoteHost(): ?string
	{
		return $this->remoteHost;
	}


	#[Override]
	public function getRawBody(): ?string
	{
		return $this->rawBody;
	}


	public function getReferer(): ?UrlImmutable
	{
		return isset($this->headers['referer']) ? new UrlImmutable($this->headers['referer']) : null;
	}


	public function isSameSite(): bool
	{
		return $this->sameSite;
	}


	public function setCookie(string $name, string $value): void
	{
		$this->cookies[$name] = $value;
	}


	public function addFile(string $name, FileUpload $fileUpload): void
	{
		$this->files[$name] = $fileUpload;
	}


	public function setMethod(string $method): void
	{
		$this->method = $method;
	}


	public function setHeader(string $name, string $value): void
	{
		$this->headers[strtolower($name)] = $value;
	}


	public function setRemoteAddress(string $remoteAddress): void
	{
		$this->remoteAddress = $remoteAddress;
	}


	public function setRemoteHost(string $remoteHost): void
	{
		$this->remoteHost = $remoteHost;
	}


	public function setRawBody(string $rawBody): void
	{
		$this->rawBody = $rawBody;
	}


	public function setSameSite(bool $sameSite): void
	{
		$this->sameSite = $sameSite;
	}


	public function resetHeaders(): void
	{
		$this->headers = [];
	}

}
