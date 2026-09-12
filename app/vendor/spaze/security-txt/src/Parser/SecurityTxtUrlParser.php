<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser;

use LogicException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotParseHostnameException;
use Uri\WhatWg\InvalidUrlException;
use Uri\WhatWg\Url;
use Uri\WhatWg\UrlValidationError;
use Uri\WhatWg\UrlValidationErrorType;

final class SecurityTxtUrlParser
{

	/**
	 * @throws SecurityTxtCannotParseHostnameException
	 */
	public function getUrl(string $url): Url
	{
		// $url = https://example.com or https://example.com/foo or https:/example.com or https:/example.com/foo
		$parsed = Url::parse($url, null, $errors);
		if ($parsed !== null) {
			if ($parsed->getUnicodeHost() === null) {
				throw new SecurityTxtCannotParseHostnameException($url);
			}
			return $parsed;
		}

		// $url = example.com or example.com/foo or /example.com or //example.com and so on
		if (
			is_array($errors)
			&& array_any($errors, fn($error): bool => $error instanceof UrlValidationError && $error->type === UrlValidationErrorType::MissingSchemeNonRelativeUrl)
		) {
			return $this->getUrl("https://{$url}");
		}

		throw new SecurityTxtCannotParseHostnameException($url);
	}


	/**
	 * The URL everything about a check derives from, the host and the port being all that decides what is checked. HTTPS is asked for rather than guaranteed: WHATWG makes the
	 * scheme setter a no-op across the special boundary, so `foo://example/` keeps the scheme it came with and `SecurityTxtFetcherUrl` is what refuses one.
	 *
	 * @throws SecurityTxtCannotParseHostnameException
	 */
	public function getBaseUrl(Url $url): Url
	{
		try {
			$stripped = $url->withUsername(null)->withPassword(null)->withScheme('https')->withPath('/')->withQuery(null)->withFragment(null);
		} catch (InvalidUrlException $e) {
			throw new LogicException("Can't set URL components: {$e->getMessage()}", previous: $e);
		}
		return $this->normalize($stripped);
	}


	/**
	 * Parsing decodes a percent escape in the host without folding the case it uncovers, so `https://ex%41mple.com/` comes out as `https://exAmple.com/` and a second parse
	 * would settle it as `https://example.com/`. Settling it here, once, before anything derives from it: the host, the URLs fetched, and the string handed to curl then all
	 * come out of the same URL and cannot disagree about which host this is. Doing it later, per derived value, is what lets them drift apart.
	 *
	 * The second parse is the whole host parser, not a case fold, so it can name a different host: an escape uncovering a punycode prefix, `https://%78%6E--%78%6E--.example/`,
	 * decodes to `xn--xn--.example` and settles as `xn-.example`. That is the parser's own reading of what was written, which is what a browser would resolve too, and it is
	 * one reading rather than two — but a caller should not read this as folding case and nothing else.
	 *
	 * A URL that will not parse a second time is refused rather than handed back unsettled. That is reachable, not theoretical: `https://%78n--a.example/` has the host
	 * `xn--a.example`, which is not valid punycode and which parsing refuses outright, so returning the original would leave a URL whose readable form is the bare string
	 * `https://`, with the host and path gone, for both fetched URLs at once.
	 *
	 * @throws SecurityTxtCannotParseHostnameException
	 */
	public function normalize(Url $url): Url
	{
		$settled = Url::parse($url->toAsciiString());
		if ($settled === null) {
			throw new SecurityTxtCannotParseHostnameException($url->toAsciiString());
		}
		return $settled;
	}


	/**
	 * @throws SecurityTxtCannotParseHostnameException
	 */
	public function getRedirectUrl(string $redirect, Url $currentUrl): Url
	{
		$redirectUrl = Url::parse($redirect, $currentUrl);
		if ($redirectUrl === null) {
			throw new SecurityTxtCannotParseHostnameException($redirect);
		}
		return $redirectUrl;
	}

}
