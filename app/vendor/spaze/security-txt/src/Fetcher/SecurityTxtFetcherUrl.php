<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtUrlUnsupportedSchemeException;
use Uri\WhatWg\Url;

final readonly class SecurityTxtFetcherUrl
{

	/**
	 * @throws SecurityTxtUrlUnsupportedSchemeException
	 */
	public function __construct(
		private Url $url,
		private SecurityTxtRedirects $redirects,
	) {
		$scheme = $url->getScheme();
		if (!in_array(strtolower($scheme), ['http', 'https'], true)) {
			throw new SecurityTxtUrlUnsupportedSchemeException($this->url, $this->redirects);
		}
	}


	public function getUrl(): Url
	{
		return $this->url;
	}


	public function getRedirects(): SecurityTxtRedirects
	{
		return $this->redirects;
	}

}
