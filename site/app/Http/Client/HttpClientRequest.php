<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Client;

final class HttpClientRequest
{

	private ?string $userAgent = null;

	/** @var list<string> */
	private array $headers = [];

	private ?bool $followLocation = null;

	private ?string $tlsServerName = null;

	private ?bool $tlsCaptureCertificate = null;


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


	public function getFollowLocation(): ?bool
	{
		return $this->followLocation;
	}


	public function setFollowLocation(bool $followLocation): self
	{
		$this->followLocation = $followLocation;
		return $this;
	}


	public function getTlsServerName(): ?string
	{
		return $this->tlsServerName;
	}


	public function setTlsServerName(string $tlsServerName): self
	{
		$this->tlsServerName = $tlsServerName;
		return $this;
	}


	public function getTlsCaptureCertificate(): ?bool
	{
		return $this->tlsCaptureCertificate;
	}


	public function setTlsCaptureCertificate(bool $tlsCaptureCertificate): self
	{
		$this->tlsCaptureCertificate = $tlsCaptureCertificate;
		return $this;
	}

}
