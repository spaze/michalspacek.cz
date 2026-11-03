<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Spaze\SecurityTxt\Fetcher\SecurityTxtRedirects;
use Throwable;

final class SecurityTxtCannotParseHostnameException extends SecurityTxtFetcherException
{

	public function __construct(string $url, ?SecurityTxtRedirects $redirects = null, ?Throwable $previous = null)
	{
		$redirects ??= new SecurityTxtRedirects();
		// No URL to hand up: this is thrown for a string that would not parse into one, so there is nothing to name but the string itself, which the values carry
		parent::__construct(
			$this->withRedirectsParam([$url], $redirects),
			"Can't parse hostname from %s" . $this->getRedirectsFormat($redirects),
			[$url, ...$redirects->getMessageValues()],
			null,
			$redirects,
			previous: $previous,
		);
	}

}
