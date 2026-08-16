<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Throwable;

final class SecurityTxtConnectedToWrongIpAddressException extends SecurityTxtFetcherException
{

	/**
	 * @param list<string> $redirects
	 */
	public function __construct(string $expectedIpAddress, string $connectedToIpAddress, string $url, array $redirects, ?Throwable $previous = null)
	{
		parent::__construct(
			[$expectedIpAddress, $connectedToIpAddress, $url, $redirects],
			$redirects !== [] ? "Can't open %s (redirects: %s" . str_repeat(' → %s', count($redirects) - 1) . '), connected to %s instead of %s as expected' : "Can't open %s, connected to %s instead of %s as expected",
			$redirects !== [] ? [$url, ...$redirects, $connectedToIpAddress, $expectedIpAddress] : [$url, $connectedToIpAddress, $expectedIpAddress],
			$url,
			$redirects,
			previous: $previous,
		);
	}

}
