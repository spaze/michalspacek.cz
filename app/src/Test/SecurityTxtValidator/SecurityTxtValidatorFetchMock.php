<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\SecurityTxtValidator;

use LogicException;
use MichalSpacekCz\Application\DependencyVersion;
use MichalSpacekCz\SecurityTxtValidator\Fetch\SecurityTxtValidatorFetch;
use MichalSpacekCz\SecurityTxtValidator\Fetch\SecurityTxtValidatorFetchResponse;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtValidatorUrl;
use MichalSpacekCz\Test\WillThrow;
use Override;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;

final class SecurityTxtValidatorFetchMock implements SecurityTxtValidatorFetch
{

	use WillThrow;


	private const string FETCHER_VERSION = '1.2.3';
	private const string FETCHER_REFERENCE = 'cafe1234';

	private int $fetches = 0;

	private ?SecurityTxtFetchResult $fetchResult = null;


	public function setFetchResult(SecurityTxtFetchResult $fetchResult): void
	{
		$this->fetchResult = $fetchResult;
	}


	/**
	 * How many times a fetch was attempted, so a test can tell a cached answer from a fetched one.
	 */
	public function getFetches(): int
	{
		return $this->fetches;
	}


	/**
	 * What the double claims fetched it, so a test can tell the fetcher's version apart from the parser's.
	 */
	public function getFetcherVersion(): DependencyVersion
	{
		return new DependencyVersion(self::FETCHER_VERSION, self::FETCHER_REFERENCE);
	}


	#[Override]
	public function fetch(SecurityTxtValidatorUrl $url, bool $requireTopLevelLocation): SecurityTxtValidatorFetchResponse
	{
		$this->fetches++;
		$this->maybeThrow();
		if ($this->fetchResult === null) {
			throw new LogicException('Set the result first with setFetchResult()');
		}
		return new SecurityTxtValidatorFetchResponse($this->fetchResult, $this->getFetcherVersion());
	}

}
