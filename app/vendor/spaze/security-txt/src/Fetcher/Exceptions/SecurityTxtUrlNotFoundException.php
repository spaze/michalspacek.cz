<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;

final class SecurityTxtUrlNotFoundException extends SecurityTxtFetcherException
{

	public function __construct(string $url, int $code, ?Throwable $previous = null)
	{
		parent::__construct([$url, $code], 'URL `%s` not found, code `%d`', [$url, $code], $url, code: $code, previous: $previous);
	}

}
