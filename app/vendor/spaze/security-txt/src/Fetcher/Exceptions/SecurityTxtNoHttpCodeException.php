<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;

final class SecurityTxtNoHttpCodeException extends SecurityTxtFetcherException
{

	/**
	 * @param list<string> $redirects
	 */
	public function __construct(string $url, array $redirects, ?Throwable $previous = null)
	{
		parent::__construct(
			[$url, $redirects],
			$redirects !== [] ? "Missing HTTP code when fetching `%s` (redirects: `%s`)" : "Missing HTTP code when fetching `%s`",
			$redirects !== [] ? [$url, implode('` => `', $redirects)] : [$url],
			$url,
			$redirects,
			previous: $previous,
		);
	}

}
