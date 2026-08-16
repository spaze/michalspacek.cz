<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;

final class SecurityTxtHostNotFoundException extends SecurityTxtFetcherException
{

	public function __construct(string $url, string $host, ?Throwable $previous = null)
	{
		parent::__construct([$url, $host], "Can't open %s, can't resolve %s", [$url, $host], $url, previous: $previous);
	}

}
