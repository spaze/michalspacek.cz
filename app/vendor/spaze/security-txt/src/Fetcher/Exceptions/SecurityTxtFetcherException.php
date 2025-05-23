<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Exception;
use JsonSerializable;
use Override;
use Throwable;

abstract class SecurityTxtFetcherException extends Exception implements JsonSerializable
{

	/**
	 * @param list<scalar|array<array-key, scalar|array<array-key, scalar>>> $constructorParams
	 * @param list<string|int> $messageValues
	 * @param list<string> $redirects
	 */
	public function __construct(
		private readonly array $constructorParams,
		private readonly string $messageFormat,
		private readonly array $messageValues,
		private readonly string $url,
		private readonly array $redirects = [],
		int $code = 0,
		?Throwable $previous = null,
	) {
		parent::__construct(vsprintf($this->messageFormat, $this->messageValues), $code, $previous);
	}


	public function getMessageFormat(): string
	{
		return $this->messageFormat;
	}


	/**
	 * @return list<string|int>
	 */
	public function getMessageValues(): array
	{
		return $this->messageValues;
	}


	public function getUrl(): string
	{
		return $this->url;
	}


	/**
	 * @return list<string>
	 */
	public function getRedirects(): array
	{
		return $this->redirects;
	}


	/**
	 * @return array<string, mixed>
	 */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'class' => $this::class,
			'params' => $this->constructorParams,
			'message' => $this->getMessage(),
			'messageFormat' => $this->getMessageFormat(),
			'messageValues' => $this->getMessageValues(),
			'url' => $this->getUrl(),
			'redirects' => $this->getRedirects(),
		];
	}

}
