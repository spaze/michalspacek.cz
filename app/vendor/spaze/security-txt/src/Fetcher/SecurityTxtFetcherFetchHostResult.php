<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;

/**
 * @internal
 */
final readonly class SecurityTxtFetcherFetchHostResult
{

	/**
	 * @phpstan-param 1|134217728 $ipAddressType DNS_A or DNS_AAAA
	 */
	public function __construct(
		private string $url,
		private string $finalUrl,
		private string $ipAddress,
		private int $ipAddressType,
		private ?SecurityTxtFetcherResponse $response,
		private ?SecurityTxtFetcherException $exception,
	) {
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
		return $this->response?->getContents();
	}


	public function isTruncated(): bool
	{
		if ($this->response === null) {
			return false;
		}
		return $this->response->isTruncated();
	}


	public function getContentTypeHeader(): ?string
	{
		return $this->response?->getHeader('Content-Type');
	}


	public function getHttpCode(): int
	{
		return $this->exception?->getCode() ?? 200;
	}

}
