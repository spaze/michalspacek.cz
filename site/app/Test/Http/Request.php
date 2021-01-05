<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Http;

use Nette\Http\FileUpload;
use Nette\Http\IRequest;
use Nette\Http\UrlImmutable;
use Nette\Http\UrlScript;

class Request implements IRequest
{

	private UrlScript $url;

	/** @var array<string, string> */
	private array $cookies;

	/** @var array<string, FileUpload> */
	private array $files;

	private string $method;

	/** @var array<string, string> */
	private array $headers;

	private ?string $remoteAddress;

	private ?string $remoteHost;

	private ?string $rawBody;

	private bool $sameSite;


	public function __construct(UrlScript $url)
	{
		$this->url = $url;
	}


	public function getUrl(): UrlScript
	{
		return $this->url;
	}


	public function getQuery(string $key = null)
	{
		return func_num_args() === 0 ? $this->url->getQueryParameters() : $this->url->getQueryParameter($key);
	}


	public function getPost(string $key = null)
	{
		// TODO: Implement getPost() method.
	}


	/**
	 * @param string $key
	 * @return FileUpload|null
	 */
	public function getFile(string $key): ?FileUpload
	{
		return $this->files[$key] ?? null;
	}


	/**
	 * @return array<string, FileUpload>
	 */
	public function getFiles(): array
	{
		return $this->files;
	}


	public function getCookie(string $key): ?string
	{
		return $this->cookies[$key] ?? null;
	}


	/**
	 * @return array<string, string>
	 */
	public function getCookies(): array
	{
		return $this->cookies;
	}


	public function getMethod(): string
	{
		return $this->method;
	}


	public function isMethod(string $method): bool
	{
		return strcasecmp($this->method, $method) === 0;
	}


	public function getHeader(string $header): ?string
	{
		return $this->headers[strtolower($header)] ?? null;
	}


	/**
	 * @return array<string, string>
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}


	public function isSecured(): bool
	{
		return $this->url->getScheme() === 'https';
	}


	public function isAjax(): bool
	{
		return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
	}


	public function getRemoteAddress(): ?string
	{
		return $this->remoteAddress;
	}


	public function getRemoteHost(): ?string
	{
		return $this->remoteHost;
	}


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
		$this->headers[$name] = $value;
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

}