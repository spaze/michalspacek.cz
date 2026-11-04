<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt;

use Uri\WhatWg\Url;

/**
 * What this library does only because `Uri\WhatWg\Url` in PHP before 8.5.12 parses some URLs wrong, kept in one place: once PHP 8.5.12 is required, this class is deleted and
 * every place static analysis then reports goes back to doing without it.
 */
final class SecurityTxtPhpUrlWorkarounds
{

	/**
	 * A serialized URL is printable ASCII, but before PHP 8.5.12 the parser copied the two bytes after a `%` that starts no escape into the path unchecked, a NUL, an escape
	 * sequence or a byte that is not UTF-8 included (fixed by lexbor/lexbor@327a8b6, merged into php-src as beee5444d0).
	 */
	public static function hasRawBytes(Url $url): bool
	{
		return preg_match('/[^\x20-\x7E]/', $url->toAsciiString()) === 1;
	}


	/**
	 * The most a `Location` header can grow to while it is parsed, worked out from the header because parsing it is what has to be avoided: before 8.5.8, PHP can return a
	 * path starting with up to a kilobyte of leftover memory of the process once the path reaches 1023 bytes while being parsed and contains one of `"`, `<`, `>`, `[`, `]`,
	 * `^`, `{`, `|`, `}`, a backtick or a non-ASCII character, `.` and `..` segments can then cut what it returns below any length checked afterwards, and PHP 8.5.4 can even
	 * move a fragment into the path. So the whole header counts, every character that may be percent-encoded as three, and so does the path of the URL it is resolved
	 * against, unless it starts with a slash and replaces that path.
	 */
	public static function getRedirectUrlMaxLength(string $location, Url $url): int
	{
		$base = str_starts_with($location, '/') || str_starts_with($location, '\\') ? '' : $url->getPath();
		$encoded = preg_match_all('~[^A-Za-z0-9._\~!$&\'()*+,;=:@/%-]~', $location);
		// Should the match fail, every character counts as encoded, the bound has to stay one
		return strlen($base) + strlen($location) + 2 * ($encoded === false ? strlen($location) : $encoded);
	}

}
