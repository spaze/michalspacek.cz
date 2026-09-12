<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;
use Uri\WhatWg\Url;

final class SecurityTxtCannotOpenUrlExtensionNotLoadedException extends SecurityTxtFetcherException
{

	public function __construct(Url $url, ?Throwable $previous = null)
	{
		parent::__construct([$url], "Cannot load security.txt from %s, the curl extension is not loaded", [$url], $url, previous: $previous);
	}

}
