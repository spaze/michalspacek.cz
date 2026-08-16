<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

/**
 * @internal
 */
final readonly class SecurityTxtFetcherFetchHostResult
{

	private ?SecurityTxtFetchHostContentType $contentType;

	private ?string $contents;

	private bool $isTruncated;

	private bool $isRegularHtmlPage;


	public function __construct(
		private string $url,
		private string $finalUrl,
		private string $ipAddress,
		private SecurityTxtIpAddressType $ipAddressType,
		private int $httpCode,
		?SecurityTxtFetcherResponse $response,
	) {
		$header = $response?->getHeader('Content-Type');
		if ($header === null) {
			$this->contentType = null;
		} else {
			$parts = explode(';', $header, 2);
			$this->contentType = new SecurityTxtFetchHostContentType(trim($parts[0]), isset($parts[1]) ? trim($parts[1]) : null);
		}
		$this->contents = $response?->getContents();
		$this->isTruncated = $response !== null && $response->isTruncated();
		$this->isRegularHtmlPage = $this->httpCode === 200
			&& $this->contentType?->getLowercaseContentType() === 'text/html'
			&& $this->contents !== null
			&& str_contains(strtolower($this->contents), '<body');
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


	public function getIpAddressType(): SecurityTxtIpAddressType
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


	public function getContentType(): ?SecurityTxtFetchHostContentType
	{
		return $this->contentType;
	}


	public function getHttpCode(): int
	{
		return $this->httpCode;
	}


	public function isRegularHtmlPage(): bool
	{
		return $this->isRegularHtmlPage;
	}

}
