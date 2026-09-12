<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt;

use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotParseHostnameException;
use Uri\WhatWg\Url;

/**
 * The host of a URL that parsed.
 *
 * Built from the URL rather than from a string on purpose: parsing refuses a host with a control character in it, so a host that came out of one cannot carry one and can be
 * printed as it reads. A host assembled from a string has no such thing behind it.
 */
final readonly class SecurityTxtHost
{

	private string $unicode;

	private string $ascii;


	/**
	 * The URL has to be settled, which is what `SecurityTxtUrlParser::normalize()` does: parsing decodes a percent escape in the host without folding the case it uncovers,
	 * so `https://ex%41mple.com/` has the host `exAmple.com`, a spelling that reads back as `example.com` and so cannot be rebuilt from what this would print.
	 *
	 * @throws SecurityTxtCannotParseHostnameException
	 */
	public function __construct(Url $url)
	{
		$ascii = $url->getAsciiHost();
		$decoded = $url->getUnicodeHost();
		// A URL parsing to a readable host of `''` has a label that is not valid punycode, and nothing resolves it. A URL that parses again as something else has not been
		// settled, and a host taken from one would read as a spelling it cannot be rebuilt from, so the caller settles it first
		if (
			$ascii === null
			|| $decoded === null
			|| $decoded === ''
			|| Url::parse($url->toAsciiString())?->getAsciiHost() !== $ascii
		) {
			throw new SecurityTxtCannotParseHostnameException($url->toUnicodeString());
		}
		$this->ascii = $ascii;
		// Which spelling this host reads as. Decoding an A-label is not always reversible: `xn--khby` decodes to U+0648 U+0654, which encodes back as `xn--jgb`, so the
		// decoded spelling would name a different host than the one that was asked for. All or nothing, so a host reads the way the URL it came out of reads: `Url`
		// serializes a host all decoded or all encoded and has no way to say a mixed one, and one name for a host is worth more than a readable label beside an
		// unreadable one
		$this->unicode = Url::parse("https://{$decoded}")?->getAsciiHost() === $ascii ? $decoded : $ascii;
	}


	public function getUnicode(): string
	{
		return $this->unicode;
	}


	public function getAscii(): string
	{
		return $this->ascii;
	}

}
