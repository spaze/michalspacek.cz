<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

/**
 * @internal
 */
final readonly class SecurityTxtFetcherFetchHostResult
{

	private ?SecurityTxtContentType $contentType;

	private ?string $contents;

	private bool $isTruncated;


	/**
	 * @phpstan-param 1|134217728 $ipAddressType DNS_A or DNS_AAAA
	 */
	public function __construct(
		private string $url,
		private string $finalUrl,
		private string $ipAddress,
		private int $ipAddressType,
		private int $httpCode,
		?SecurityTxtFetcherResponse $response,
	) {
		$header = $response?->getHeader('Content-Type');
		if ($header === null) {
			$this->contentType = null;
		} else {
			$parts = explode(';', $header, 2);
			$this->contentType = new SecurityTxtContentType($parts[0], $parts[1] ?? null);
		}
		$this->contents = $response?->getContents();
		$this->isTruncated = $response !== null && $response->isTruncated();
	}


	public function getUrl(): string
	{
		return $this->url;
	}


	public function getFinalUrl(): string
	{
		return $this->finalUrl;
	}


	public function getIpAddress(): string
	{
		return $this->ipAddress;
	}


	/**
	 * @phpstan-return 1|134217728 DNS_A or DNS_AAAA
	 */
	public function getIpAddressType(): int
	{
		return $this->ipAddressType;
	}


	public function getContents(): ?string
	{
		return $this->contents;
	}


	public function isTruncated(): bool
	{
		return $this->isTruncated;
	}


	public function getContentType(): ?SecurityTxtContentType
	{
		return $this->contentType;
	}


	public function getHttpCode(): int
	{
		return $this->httpCode;
	}

}
