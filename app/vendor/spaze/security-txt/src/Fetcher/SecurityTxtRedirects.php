<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

use Countable;
use Override;
use Spaze\SecurityTxt\SecurityTxtPrintableValue;
use Uri\WhatWg\Url;

/**
 * Where a check was sent on its way to a file, in the order it went.
 *
 * Held as the spellings a stored result carries rather than as `Url` objects, because not every URL this library records has one that reads back: a redirect to
 * `ftp://%78n--a.example/` resolves fine and serializes as `ftp://xn--a.example/`, which `Url` will not parse in any spelling. A chain of objects would have to refuse that,
 * and refusing it takes down the whole stored result the chain is only a part of.
 *
 * What the chain does own is the reading, once, for both the message built here and the message built from a replay, so the two cannot say a redirect differently. That is
 * why this is a type at all: `SecurityTxtJson` decides the way back from what a constructor parameter is declared as, and `array` says nothing about its elements.
 */
final readonly class SecurityTxtRedirects implements Countable
{

	/** @var list<string> */
	private array $urls;


	/**
	 * @param string ...$urls Each in the spelling a stored result carries, which is what a replay hands back
	 */
	public function __construct(string ...$urls)
	{
		// A variadic collects a named argument under its name, so what it holds is a list only after this
		$this->urls = array_values($urls);
	}


	public function withRedirect(Url $url): self
	{
		return new self(...[...$this->urls, new SecurityTxtPrintableValue($url)->render()]);
	}


	/**
	 * @return list<string>
	 */
	public function toStrings(): array
	{
		return $this->urls;
	}


	/**
	 * The chain as message values, so a redirect prints as the URL it is rather than as text a checked host sent, which `SecurityTxtPrintableValue` percent encodes. One that
	 * does not read back stays the string it is, which is the spelling this library wrote and already printable.
	 *
	 * @return list<string|Url>
	 */
	public function getMessageValues(): array
	{
		return array_map(fn(string $url): string|Url => Url::parse($url) ?? $url, $this->urls);
	}


	#[Override]
	public function count(): int
	{
		return count($this->urls);
	}

}
