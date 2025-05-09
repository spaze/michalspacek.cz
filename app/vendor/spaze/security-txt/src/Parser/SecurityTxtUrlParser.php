<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser;

use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotParseHostnameException;

final class SecurityTxtUrlParser
{

	/**
	 * @throws SecurityTxtCannotParseHostnameException
	 */
	public function getHostFromUrl(string $url): string
	{
		// $url = https://example.com or https://example.com/foo
		$components = parse_url($url);
		if ($components !== false && isset($components['host'])) {
			return $components['host'];
		}

		// $url = https:/example.com or https:/example.com/foo
		if ($components !== false && isset($components['scheme'], $components['path']) && !isset($components['host'])) {
			$host = parse_url("{$components['scheme']}:/{$components['path']}", PHP_URL_HOST);
			if ($host !== false && $host !== null) {
				return $host;
			}
		}

		// $url = example.com or example.com/foo
		$components = parse_url("//$url", PHP_URL_HOST);
		if ($components !== false && $components !== null) {
			return $components;
		}

		throw new SecurityTxtCannotParseHostnameException($url);
	}

}
