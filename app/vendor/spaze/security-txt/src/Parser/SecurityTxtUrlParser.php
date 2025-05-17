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


	public function getRedirectUrl(string $redirect, string $current): string
	{
		$redirectParts = parse_url($redirect);
		if ($redirectParts === false) {
			return $redirect;
		}
		if (!isset($redirectParts['host'])) {
			$currentParts = parse_url($current);
			if ($currentParts === false) {
				return $redirect;
			}
			if (!isset($redirectParts['path'])) {
				$redirectParts['path'] = '/';
			}
			if (!isset($currentParts['path'])) {
				$currentParts['path'] = '/';
			}
			if ($redirectParts['path'][0] === '/') {
				$currentParts['path'] = $redirectParts['path'];
			} else {
				$currentParts['path'] = sprintf('%s/%s', rtrim(dirname($currentParts['path']), '/'), $redirectParts['path']);
			}
			if (isset($redirectParts['query'])) {
				$currentParts['query'] = $redirectParts['query'];
			} else {
				unset($currentParts['query']);
			}
			if (isset($redirectParts['fragment'])) {
				$currentParts['fragment'] = $redirectParts['fragment'];
			} else {
				unset($currentParts['fragment']);
			}
			$redirect = $this->getUrl($currentParts);
		}
		return $redirect;
	}


	/**
	 * @param array{scheme?:string, user?:string, pass?:string, host?:string, port?:int, path:string, query?:string, fragment?:string} $parts
	 */
	private function getUrl(array $parts): string
	{
		$url = '';
		if (isset($parts['scheme'])) {
			$url .= "{$parts['scheme']}://";
		}
		if (isset($parts['host'])) {
			$url .= $parts['host'];
		}
		if (isset($parts['port'])) {
			$url .= ":{$parts['port']}";
		}
		$url .= $parts['path'];
		if (isset($parts['query'])) {
			$url .= "?{$parts['query']}";
		}
		if (isset($parts['fragment'])) {
			$url .= "#{$parts['fragment']}";
		}
		return $url;
	}

}
