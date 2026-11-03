<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;

final class SecurityTxtTopLevelPathOnly extends SecurityTxtSpecViolation
{

	/**
	 * @param int|null $wellKnownHttpCode What the /.well-known/ location answered with, when that was an error other than one saying there is no file there
	 * @param SecurityTxtFetcherException|null $wellKnownException Why the /.well-known/ location could not be read at all
	 */
	public function __construct(?int $wellKnownHttpCode = null, ?SecurityTxtFetcherException $wellKnownException = null)
	{
		if ($wellKnownException !== null) {
			$format = "%s wasn't found under the %s path: " . $wellKnownException->getMessageFormat();
			$values = ['security.txt', '/.well-known/', ...$wellKnownException->getMessageValues()];
			$howToFixFormat = 'Fix what keeps %s from being read, and redirect %s to it';
			$howToFixValues = ['/.well-known/security.txt', '/security.txt'];
		} elseif ($wellKnownHttpCode === null) {
			$format = "%s wasn't found under the %s path";
			$values = ['security.txt', '/.well-known/'];
			$howToFixFormat = 'Move the %s file from the top-level location under the %s path and redirect %s to %s';
			$howToFixValues = ['security.txt', '/.well-known/', '/security.txt', '/.well-known/security.txt'];
		} else {
			$format = '%s answered with HTTP code %s, only the top-level %s was found';
			$values = ['/.well-known/security.txt', (string)$wellKnownHttpCode, 'security.txt'];
			$howToFixFormat = 'Make %s available to everyone, a firewall or a bot protection may be what answers instead, and redirect %s to it';
			$howToFixValues = ['/.well-known/security.txt', '/security.txt'];
		}
		parent::__construct(
			func_get_args(),
			$format,
			$values,
			'draft-foudil-securitytxt-02',
			null,
			$howToFixFormat,
			$howToFixValues,
			'3',
		);
	}

}
