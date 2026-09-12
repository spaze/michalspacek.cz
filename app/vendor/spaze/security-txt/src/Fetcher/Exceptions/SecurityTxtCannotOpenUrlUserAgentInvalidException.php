<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;
use Uri\WhatWg\Url;

final class SecurityTxtCannotOpenUrlUserAgentInvalidException extends SecurityTxtFetcherException
{

	public function __construct(Url $url, ?Throwable $previous = null)
	{
		parent::__construct(
			[$url],
			"Can't open %s, the specified user agent contains a control character and is invalid",
			[$url],
			$url,
			previous: $previous,
		);
	}

}
