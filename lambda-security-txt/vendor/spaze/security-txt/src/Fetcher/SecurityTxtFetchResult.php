<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

use JsonSerializable;
use Override;
use Spaze\SecurityTxt\Json\SecurityTxtJson;
use Spaze\SecurityTxt\SecurityTxtPrintableValue;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;
use Uri\WhatWg\Url;

final readonly class SecurityTxtFetchResult implements JsonSerializable
{

	/**
	 * @param array<string, SecurityTxtRedirects> $redirects
	 * @param array<int, string> $lines
	 * @param list<SecurityTxtSpecViolation> $errors
	 * @param list<SecurityTxtSpecViolation> $warnings
	 */
	public function __construct(
		private Url $constructedUrl,
		private Url $finalUrl,
		private array $redirects,
		private string $contents,
		private bool $isTruncated,
		private array $lines,
		private array $errors,
		private array $warnings,
	) {
	}


	/**
	 * The file contents fetched from the host, do not render as HTML or feed it to a Markdown parser, could be malicious.
	 */
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


	public function getFinalUrl(): Url
	{
		return $this->finalUrl;
	}


	public function getConstructedUrl(): Url
	{
		return $this->constructedUrl;
	}


	/**
	 * The redirect URLs, do not render as HTML or Markdown, could be malicious.
	 *
	 * @return array<string, SecurityTxtRedirects>
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
			'formatVersion' => SecurityTxtJson::FORMAT_VERSION,
			// Spelled the way this library spells one, not decoded: `toUnicodeString()` on a host whose punycode does not survive decoding writes a URL naming another host,
			// which `SecurityTxtJson` then refuses as not a URL this library writes, taking a whole stored result down over a URL nobody stored
			'constructedUrl' => new SecurityTxtPrintableValue($this->getConstructedUrl())->render(),
			'finalUrl' => new SecurityTxtPrintableValue($this->getFinalUrl())->render(),
			'redirects' => array_map(fn(SecurityTxtRedirects $redirects): array => $redirects->toStrings(), $this->getRedirects()),
			'contents' => $this->getContents(),
			'isTruncated' => $this->isTruncated(),
			'errors' => $this->getErrors(),
			'warnings' => $this->getWarnings(),
		];
	}

}
