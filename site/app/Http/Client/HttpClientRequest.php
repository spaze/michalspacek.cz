<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Client;

final class HttpClientRequest
{

	private ?string $userAgent = null;

	/** @var list<string> */
	private array $headers = [];


	public function __construct(
		private readonly string $url,
	) {
	}


	public function getUrl(): string
	{
		return $this->url;
	}


	public function getUserAgent(): ?string
	{
		return $this->userAgent;
	}


	public function setUserAgent(string $userAgent): self
	{
		$this->userAgent = $userAgent;
		return $this;
	}


	public function addHeader(string $header, string $value): self
	{
		$this->headers[] = "{$header}: {$value}";
		return $this;
	}


	/**
	 * @return list<string>
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

}
