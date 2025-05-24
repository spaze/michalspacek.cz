<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

use JsonSerializable;
use Override;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;

final readonly class SecurityTxtFetchResult implements JsonSerializable
{

	/**
	 * @param array<string, list<string>> $redirects
	 * @param array<int, string> $lines
	 * @param list<SecurityTxtSpecViolation> $errors
	 * @param list<SecurityTxtSpecViolation> $warnings
	 */
	public function __construct(
		private string $constructedUrl,
		private string $finalUrl,
		private array $redirects,
		private string $contents,
		private bool $isTruncated,
		private array $lines,
		private array $errors,
		private array $warnings,
	) {
	}


	public function getContents(): string
	{
		return $this->contents;
	}


	public function isTruncated(): bool
	{
		return $this->isTruncated;
	}


	/**
	 * @param int<1, max> $line
	 * @return string|null
	 */
	public function getLine(int $line): ?string
	{
		return $this->lines[$line - 1] ?? null;
	}


	public function getFinalUrl(): string
	{
		return $this->finalUrl;
	}


	public function getConstructedUrl(): string
	{
		return $this->constructedUrl;
	}


	/**
	 * @return array<string, list<string>>
	 */
	public function getRedirects(): array
	{
		return $this->redirects;
	}


	/**
	 * @return list<SecurityTxtSpecViolation>
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}


	/**
	 * @return list<SecurityTxtSpecViolation>
	 */
	public function getWarnings(): array
	{
		return $this->warnings;
	}


	/**
	 * @return array<string, mixed>
	 */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'class' => $this::class,
			'constructedUrl' => $this->getConstructedUrl(),
			'finalUrl' => $this->getFinalUrl(),
			'redirects' => $this->getRedirects(),
			'contents' => $this->getContents(),
			'isTruncated' => $this->isTruncated(),
			'errors' => $this->getErrors(),
			'warnings' => $this->getWarnings(),
		];
	}

}
