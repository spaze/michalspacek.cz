<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\SecurityTxtValidator;

use LogicException;
use MichalSpacekCz\SecurityTxtValidator\Fetch\SecurityTxtValidatorFetch;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtValidatorUrl;
use MichalSpacekCz\Test\WillThrow;
use Override;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;

final class SecurityTxtValidatorFetchMock implements SecurityTxtValidatorFetch
{

	use WillThrow;


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


	#[Override]
	public function fetch(SecurityTxtValidatorUrl $url, bool $requireTopLevelLocation): SecurityTxtFetchResult
	{
		$this->fetches++;
		$this->maybeThrow();
		if ($this->fetchResult === null) {
			throw new LogicException('Set the result first with setFetchResult()');
		}
		return $this->fetchResult;
	}

}
