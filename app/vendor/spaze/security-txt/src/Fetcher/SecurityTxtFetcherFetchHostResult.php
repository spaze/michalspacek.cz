<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

use JsonSerializable;
use Override;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;

/**
 * @internal
 */
final readonly class SecurityTxtFetcherFetchHostResult implements JsonSerializable
{

	public function __construct(
		private string $url,
		private string $finalUrl,
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


	public function getContents(): ?string
	{
		return $this->response?->getContents();
	}


	public function getContentTypeHeader(): ?string
	{
		return $this->response?->getHeader('Content-Type');
	}


	public function getHttpCode(): int
	{
		return $this->exception?->getCode() ?? 200;
	}


	/**
	 * @return array<string, mixed>
	 */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'url' => $this->getUrl(),
			'finalUrl' => $this->getFinalUrl(),
			'contents' => $this->getContents(),
			'httpCode' => $this->getHttpCode(),
		];
	}

}
