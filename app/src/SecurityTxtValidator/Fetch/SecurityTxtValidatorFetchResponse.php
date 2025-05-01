<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Fetch;

use MichalSpacekCz\Application\DependencyVersion;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;

/**
 * What was fetched together with the version of the library that fetched it, because in production the fetcher is
 * a deployment of its own and the version that parses the result is not always the version that fetched it.
 */
final readonly class SecurityTxtValidatorFetchResponse
{

	public function __construct(
		private SecurityTxtFetchResult $fetchResult,
		private DependencyVersion $fetcherVersion,
	) {
	}


	public function getFetchResult(): SecurityTxtFetchResult
	{
		return $this->fetchResult;
	}


	public function getFetcherVersion(): DependencyVersion
	{
		return $this->fetcherVersion;
	}

}
