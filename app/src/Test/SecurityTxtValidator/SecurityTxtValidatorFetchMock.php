<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\SecurityTxtValidator;

use Closure;
use LogicException;
use MichalSpacekCz\Application\DependencyVersion;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorFetchFailedException;
use MichalSpacekCz\SecurityTxtValidator\Fetch\SecurityTxtValidatorFetch;
use MichalSpacekCz\SecurityTxtValidator\Fetch\SecurityTxtValidatorFetchResponse;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtValidatorUrl;
use MichalSpacekCz\Test\WillThrow;
use Override;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;

final class SecurityTxtValidatorFetchMock implements SecurityTxtValidatorFetch
{

	use WillThrow;


	private const string FETCHER_VERSION = '1.2.3';
	private const string FETCHER_REFERENCE = 'cafe1234';

	private int $fetches = 0;

	private ?SecurityTxtFetchResult $fetchResult = null;

	private ?Closure $whileFetching = null;


	public function reset(): void
	{
		$this->fetches = 0;
		$this->fetchResult = null;
		$this->whileFetching = null;
		$this->wontThrow();
	}


	public function setFetchResult(SecurityTxtFetchResult $fetchResult): void
	{
		$this->fetchResult = $fetchResult;
	}


	/**
	 * Runs inside fetch(), for a test that needs the world to move on while a fetch is in flight, the clock above all.
	 */
	public function whileFetching(Closure $callback): void
	{
		$this->whileFetching = $callback;
	}


	/**
	 * How many times a fetch was attempted, so a test can tell a cached response from a fetched one.
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
		if ($this->whileFetching !== null) {
			($this->whileFetching)();
		}
		try {
			$this->maybeThrow();
		} catch (SecurityTxtFetcherException $e) {
			// A test hands over the library's exception, and it comes out wrapped the way the real fetchers wrap it
			throw new SecurityTxtValidatorFetchFailedException($e, $this->getFetcherVersion());
		}
		if ($this->fetchResult === null) {
			throw new LogicException('Set the result first with setFetchResult()');
		}
		return new SecurityTxtValidatorFetchResponse($this->fetchResult, $this->getFetcherVersion());
	}

}
