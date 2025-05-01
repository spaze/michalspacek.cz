<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Fetch;

use MichalSpacekCz\SecurityTxtValidator\SecurityTxtValidatorUrl;
use Override;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetcher;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;

final readonly class SecurityTxtValidatorDirectFetch implements SecurityTxtValidatorFetch
{

	public function __construct(
		private SecurityTxtFetcher $securityTxtFetcher,
		private bool $noIpv6,
	) {
	}


	/**
	 * @throws SecurityTxtFetcherException
	 */
	#[Override]
	public function fetch(SecurityTxtValidatorUrl $url, bool $requireTopLevelLocation): SecurityTxtFetchResult
	{
		return $this->securityTxtFetcher->fetch($url->getUrl(), false, $this->noIpv6);
	}

}
