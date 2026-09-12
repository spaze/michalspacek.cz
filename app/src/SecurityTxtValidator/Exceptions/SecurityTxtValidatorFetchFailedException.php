<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Exceptions;

use Exception;
use MichalSpacekCz\Application\DependencyVersion;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;

/**
 * The fetcher's exception is the host's response and gets stored like a result does, so the version of the fetcher
 * that produced it travels with it the way it travels with a result.
 */
final class SecurityTxtValidatorFetchFailedException extends Exception
{

	public function __construct(
		private readonly SecurityTxtFetcherException $fetcherException,
		private readonly DependencyVersion $fetcherVersion,
	) {
		parent::__construct($this->fetcherException->getMessage(), previous: $this->fetcherException);
	}


	public function getFetcherException(): SecurityTxtFetcherException
	{
		return $this->fetcherException;
	}


	public function getFetcherVersion(): DependencyVersion
	{
		return $this->fetcherVersion;
	}

}
