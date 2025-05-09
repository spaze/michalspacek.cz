<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;

final class SecurityTxtNoLocationHeaderException extends SecurityTxtFetcherException
{

	public function __construct(string $url, int $httpCode, ?Throwable $previous = null)
	{
		parent::__construct(
			[$url, $httpCode],
			'HTTP response with code `%d` is missing a Location header when fetching `%s`',
			[$httpCode, $url],
			$url,
			previous: $previous,
		);
	}

}
