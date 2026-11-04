<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

use Uri\WhatWg\Url;

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
		private Url $url,
		private Url $finalUrl,
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
			$this->contentType = new SecurityTxtFetchHostContentType(trim($parts[0]), $parts[1] ?? null);
		}
		$this->contents = $response?->getContents();
		$this->isTruncated = $response !== null && $response->isTruncated();
		// One that starts like an HTML document is one whatever its Content-Type, a file can't start like that
		$this->isRegularHtmlPage = $this->contents !== null
			&& (
				$this->contentType?->getLowercaseContentType() === 'text/html' && str_contains(strtolower($this->contents), '<body')
				|| preg_match('/^(?:\xEF\xBB\xBF)?\s*+<(?:!doctype\s++html|html)[\s>]/i', $this->contents) === 1
			);
	}


	public function getUrl(): Url
	{
		return $this->url;
	}


	public function getFinalUrl(): Url
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
