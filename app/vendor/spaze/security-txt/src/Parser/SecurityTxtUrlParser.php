<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser;

use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotParseHostnameException;
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
