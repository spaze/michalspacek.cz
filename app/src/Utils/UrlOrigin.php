<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

use MichalSpacekCz\Utils\Exceptions\UrlOriginNoHostException;
use Uri\WhatWg\Url;

final class UrlOrigin
{

	/**
	 * Get origin from URL.
	 *
	 * Supports only a subset of the schemes listed in the URL Living Standard https://url.spec.whatwg.org/#origin
	 *
	 * @throws UrlOriginNoHostException
	 */
	public function getFromUrl(Url $url): ?string
	{
		$scheme = $url->getScheme();
		if (!in_array($scheme, ['ftp', 'http', 'https', 'ws', 'wss'], true)) {
			return null;
		}
		$host = $url->getUnicodeHost();
		if ($host === null || $host === '') {
			throw new UrlOriginNoHostException($url);
		}
		$port = $url->getPort();
		return sprintf(
			'%s://%s%s',
			$scheme,
			$host,
			$port !== null ? ":{$port}" : '',
		);
	}

}
