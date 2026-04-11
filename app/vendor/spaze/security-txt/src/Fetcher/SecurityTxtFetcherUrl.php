<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtUrlUnsupportedSchemeException;
use Uri\WhatWg\Url;

final readonly class SecurityTxtFetcherUrl
{

	/**
	 * @param list<string> $redirects
	 * @throws SecurityTxtUrlUnsupportedSchemeException
	 */
	public function __construct(
		private Url $url,
		private array $redirects,
	) {
		$scheme = $url->getScheme();
		if (!in_array(strtolower($scheme), ['http', 'https'], true)) {
			throw new SecurityTxtUrlUnsupportedSchemeException($this->url->toUnicodeString(), $this->redirects);
		}
	}


	public function getUrl(): Url
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

}
