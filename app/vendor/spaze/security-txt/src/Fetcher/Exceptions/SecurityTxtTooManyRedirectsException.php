<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\Fetcher\SecurityTxtRedirects;
use Throwable;
use Uri\WhatWg\Url;

final class SecurityTxtTooManyRedirectsException extends SecurityTxtFetcherException
{

	/**
	 * @param int $maxAllowed
	 * @param Throwable|null $previous
	 */
	public function __construct(Url $url, SecurityTxtRedirects $redirects, int $maxAllowed, ?Throwable $previous = null)
	{
		parent::__construct(
			[$url, $redirects, $maxAllowed],
			"Can't read %s, too many redirects, max allowed is %s" . $this->getRedirectsFormat($redirects, ', the last one not loaded'),
			[$url, (string)$maxAllowed, ...$redirects->getMessageValues()],
			$url,
			$redirects,
			previous: $previous,
		);
	}

}
