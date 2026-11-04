<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;

final class SecurityTxtWellKnownPathOnly extends SecurityTxtSpecViolation
{

	/**
	 * @param SecurityTxtFetcherException|null $topLevelException Why the top-level location could not be read at all
	 */
	public function __construct(?SecurityTxtFetcherException $topLevelException = null)
	{
		if ($topLevelException !== null) {
			$format = "%s wasn't found at the top-level path: " . $topLevelException->getMessageFormat();
			$values = ['security.txt', ...$topLevelException->getMessageValues()];
			$howToFixFormat = 'Make %s readable and redirect it to the file under the %s path';
			$howToFixValues = ['/security.txt', '/.well-known/'];
		} else {
			$format = "%s wasn't found at the top-level path";
			$values = ['security.txt'];
			$howToFixFormat = 'Redirect the top-level file to the one under the %s path';
			$howToFixValues = ['/.well-known/'];
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
