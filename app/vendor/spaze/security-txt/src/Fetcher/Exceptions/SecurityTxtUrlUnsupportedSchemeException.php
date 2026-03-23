<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;

final class SecurityTxtUrlUnsupportedSchemeException extends SecurityTxtFetcherException
{

	/**
	 * @param list<string> $redirects
	 */
	public function __construct(string $url, array $redirects, ?Throwable $previous = null)
	{
		parent::__construct(
			[$url, $redirects],
			$redirects !== [] ? 'URL %s has an unsupported scheme (redirects: %s' . str_repeat(' → %s', count($redirects) - 1) . ')' : 'URL %s has an unsupported scheme',
			$redirects !== [] ? [$url, ...$redirects] : [$url],
			$url,
			$redirects,
			previous: $previous,
		);
	}

}
