<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Fetch;

use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorFetchFailedException;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtLibraryVersion;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtValidatorUrl;
use Override;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetcher;

final readonly class SecurityTxtValidatorDirectFetch implements SecurityTxtValidatorFetch
{

	public function __construct(
		private SecurityTxtFetcher $securityTxtFetcher,
		private SecurityTxtLibraryVersion $libraryVersion,
		private bool $noIpv6,
	) {
	}


	/**
	 * @throws SecurityTxtValidatorFetchFailedException
	 */
	#[Override]
	public function fetch(SecurityTxtValidatorUrl $url, bool $requireTopLevelLocation): SecurityTxtValidatorFetchResponse
	{
		$fetcherVersion = $this->libraryVersion->getInstalled();
		try {
			return new SecurityTxtValidatorFetchResponse($this->securityTxtFetcher->fetch($url->getBaseUrl(), false, $this->noIpv6), $fetcherVersion);
		} catch (SecurityTxtFetcherException $e) {
			throw new SecurityTxtValidatorFetchFailedException($e, $fetcherVersion);
		}
	}

}
