<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorHostException;
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


	public function getSecurityTxtHost(): SecurityTxtHost
	{
		return $this->host;
	}


	public function getBaseUrl(): Url
	{
		return $this->baseUrl;
	}

}
