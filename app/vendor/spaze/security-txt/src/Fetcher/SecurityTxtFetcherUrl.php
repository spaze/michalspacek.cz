<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtUrlNoSchemeException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtUrlUnsupportedSchemeException;

final readonly class SecurityTxtFetcherUrl
{

	/**
	 * @param list<string> $redirects
	 * @throws SecurityTxtUrlNoSchemeException
	 * @throws SecurityTxtUrlUnsupportedSchemeException
	 */
	public function __construct(
		private string $url,
		private array $redirects,
	) {
		$scheme = parse_url($this->url, PHP_URL_SCHEME);
		if ($scheme === null || $scheme === false) {
			throw new SecurityTxtUrlNoSchemeException($this->url, $this->redirects);
		}
		if (!in_array(strtolower($scheme), ['http', 'https'], true)) {
			throw new SecurityTxtUrlUnsupportedSchemeException($this->url, $this->redirects);
		}
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

}
