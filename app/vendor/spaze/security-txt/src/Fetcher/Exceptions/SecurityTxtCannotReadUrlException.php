<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;

final class SecurityTxtCannotReadUrlException extends SecurityTxtFetcherException
{

	/**
	 * @param list<string> $redirects
	 */
	public function __construct(string $url, array $redirects, ?Throwable $previous = null)
	{
		parent::__construct(
			[$url, $redirects],
			$redirects !== [] ? "Can't get contents of `%s` (redirects: `%s`)" : "Can't get contents of `%s`",
			$redirects !== [] ? [$url, implode('` → `', $redirects)] : [$url],
			$url,
			$redirects,
			previous: $previous,
		);
	}

}
