<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

final readonly class SecurityTxtFetcherResponse
{

	/**
	 * @param array<string, string> $headers lowercase name => value
	 */
	public function __construct(
		private int $httpCode,
		private array $headers,
		private string $contents,
		private bool $isTruncated,
	) {
	}


	public function getHttpCode(): int
	{
		return $this->httpCode;
	}


	public function getHeader(string $header): ?string
	{
		return $this->headers[strtolower($header)] ?? null;
	}


	public function getContents(): string
	{
		return $this->contents;
	}


	public function isTruncated(): bool
	{
		return $this->isTruncated;
	}

}
