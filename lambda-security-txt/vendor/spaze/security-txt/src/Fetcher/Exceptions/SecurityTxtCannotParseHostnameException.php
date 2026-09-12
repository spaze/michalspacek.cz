<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;

final class SecurityTxtCannotParseHostnameException extends SecurityTxtFetcherException
{

	public function __construct(string $url, ?Throwable $previous = null)
	{
		// No URL to hand up: this is thrown for a string that would not parse into one, so there is nothing to name but the string itself, which the values carry
		parent::__construct([$url], "Can't parse hostname from %s", [$url], null, previous: $previous);
	}

}
