<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorHostException;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Http\Url as NetteUrl;
use Nette\Utils\Html;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotParseHostnameException;
use Spaze\SecurityTxt\SecurityTxtHost;
use Uri\WhatWg\Url;

/**
 * A "wrapper" for Uri\WhatWg\Url which always has a host, unlike Uri\WhatWg\Url.
 */
final readonly class SecurityTxtValidatorUrl
{

	private SecurityTxtHost $host;


	/**
	 * @throws SecurityTxtValidatorHostException
	 */
	public function __construct(private Url $baseUrl)
	{
		try {
			$this->host = new SecurityTxtHost($baseUrl);
		} catch (SecurityTxtCannotParseHostnameException $e) {
			throw new SecurityTxtValidatorHostException(Html::fromText('No hostname'), previous: $e);
		}
	}


	/**
	 * The hostname to show people, `exámple.com` rather than `xn--exmple-qta.com`.
	 */
	public function getHost(): string
	{
		return $this->host->getUnicode();
	}


	/**
	 * The hostname with no accents to store and look things up by, used for example as a cache key.
	 */
	public function getAsciiHost(): string
	{
		return $this->host->getAscii();
	}


	public function getScheme(): string
	{
		return $this->baseUrl->getScheme();
	}


	/**
	 * The port the file would be fetched from, named even when it is the default one, which the URL parser normalizes
	 * away to nothing. Stored beside the scheme and the host because those three together are what decides which file
	 * gets checked, so a check of one port cannot answer for another.
	 *
	 * @throws ShouldNotHappenException A scheme with no default port, which `SecurityTxtValidatorHost` refuses before
	 *     anything gets this far.
	 */
	public function getPort(): int
	{
		$port = $this->baseUrl->getPort();
		if ($port !== null) {
			return $port;
		}
		$defaultPort = NetteUrl::$defaultPorts[$this->getScheme()] ?? null;
		if ($defaultPort === null) {
			throw new ShouldNotHappenException("No default port known for the {$this->getScheme()} scheme");
		}
		return $defaultPort;
	}


	/**
	 * Whether the port is the one the scheme brings with it, which the URL parser says by normalizing it away to
	 * nothing. `https://example.com` and `https://example.com:443` are the same origin and answer the same here.
	 */
	public function isDefaultPort(): bool
	{
		return $this->baseUrl->getPort() === null;
	}


	public function getSecurityTxtHost(): SecurityTxtHost
	{
		return $this->host;
	}


	public function getBaseUrl(): Url
	{
		return $this->baseUrl;
	}

}
