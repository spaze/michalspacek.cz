<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorHostException;
use Nette\Utils\Html;
use Uri\WhatWg\Url;

/**
 * A "wrapper" for Uri\WhatWg\Url which always has a host, unlike Uri\WhatWg\Url.
 */
final readonly class SecurityTxtValidatorUrl
{

	private string $host;

	private string $asciiHost;


	/**
	 * @throws SecurityTxtValidatorHostException
	 */
	public function __construct(private Url $url)
	{
		$unicodeHost = $url->getUnicodeHost();
		$asciiHost = $url->getAsciiHost();
		if ($unicodeHost === null || $unicodeHost === '' || $asciiHost === null || $asciiHost === '') {
			throw new SecurityTxtValidatorHostException(Html::fromText('No hostname'));
		}
		$this->host = $unicodeHost;
		$this->asciiHost = $asciiHost;
	}


	/**
	 * The hostname to show people, `exámple.com` rather than `xn--exmple-qta.com`.
	 */
	public function getHost(): string
	{
		return $this->host;
	}


	/**
	 * The hostname with no accents to store and look things up by, used for example as a cache key.
	 */
	public function getAsciiHost(): string
	{
		return $this->asciiHost;
	}


	public function getUrl(): Url
	{
		return $this->url;
	}

}
