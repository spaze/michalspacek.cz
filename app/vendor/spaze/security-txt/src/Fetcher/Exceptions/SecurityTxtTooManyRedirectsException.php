<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;

final class SecurityTxtTooManyRedirectsException extends SecurityTxtFetcherException
{

	/**
	 * @param string $url
	 * @param list<string> $redirects
	 * @param int $maxAllowed
	 * @param Throwable|null $previous
	 */
	public function __construct(string $url, array $redirects, int $maxAllowed, ?Throwable $previous = null)
	{
		parent::__construct(
			[$url, $redirects, $maxAllowed],
			"Can't read %s, too many redirects, max allowed is %s (%s" . str_repeat(' → %s', count($redirects) - 1) . ' [not loaded])',
			[$url, (string)$maxAllowed, ...$redirects],
			$url,
			previous: $previous,
		);
	}

}
