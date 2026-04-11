<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;

final class SecurityTxtCannotOpenUrlExtensionNotLoadedException extends SecurityTxtFetcherException
{

	public function __construct(string $url, ?Throwable $previous = null)
	{
		parent::__construct([$url], "Cannot load security.txt from %s, the curl extension is not loaded", [$url], $url, previous: $previous);
	}

}
