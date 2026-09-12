<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Check;

use DateTimeImmutable;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlExtensionNotLoadedException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlUserAgentInvalidException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotParseHostnameException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtConnectedToWrongIpAddressException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressInvalidException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressNotPublicException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNoHttpCodeException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNoLocationHeaderException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtOnlyIpv6HostButIpv6DisabledException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtTooManyRedirectsException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtUrlUnsupportedSchemeException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetcher;
use Spaze\SecurityTxt\Parser\SecurityTxtParser;
use Spaze\SecurityTxt\Parser\SecurityTxtUrlParser;
use Spaze\SecurityTxt\SecurityTxtHost;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;
use Uri\WhatWg\Url;

final class SecurityTxtCheckHost
{

	/** @var list<callable(Url): void> */
	private array $onUrl = [];

	/** @var list<callable(Url): void> */
	private array $onFinalUrl = [];

	/** @var list<callable(Url, Url): void> */
	private array $onRedirect = [];

	/** @var list<callable(Url): void> */
	private array $onUrlNotFound = [];

	/** @var list<callable(positive-int, DateTimeImmutable): void> */
	private array $onIsExpired = [];

	/** @var list<callable(positive-int, DateTimeImmutable): void> */
	private array $onExpires = [];

	/** @var list<callable(SecurityTxtHost): void> */
	private array $onHost = [];

	/** @var list<callable(string, DateTimeImmutable): void> */
	private array $onValidSignature = [];

	/** @var list<callable(?int, SecurityTxtSpecViolation): void> */
	private array $onFetchError = [];

	/** @var list<callable(?int, SecurityTxtSpecViolation): void> */
	private array $onLineError = [];

	/** @var list<callable(?int, SecurityTxtSpecViolation): void> */
	private array $onFileError = [];

	/** @var list<callable(?int, SecurityTxtSpecViolation): void> */
	private array $onFetchWarning = [];

	/** @var list<callable(?int, SecurityTxtSpecViolation): void> */
	private array $onLineWarning = [];

	/** @var list<callable(?int, SecurityTxtSpecViolation): void> */
	private array $onFileWarning = [];


	public function __construct(
		private readonly SecurityTxtParser $parser,
		private readonly SecurityTxtFetcher $fetcher,
		private readonly SecurityTxtCheckHostResultFactory $resultFactory,
		private readonly SecurityTxtUrlParser $urlParser,
	) {
		$this->initFetcherCallbacks();
	}


	/**
	 * @param Url $url Only the host and port parts of the URL will be used
	 * @param non-negative-int|null $maxAllowedRedirects
	 * @throws SecurityTxtHostNotFoundException
	 * @throws SecurityTxtCannotParseHostnameException
	 * @throws SecurityTxtCannotOpenUrlExtensionNotLoadedException
	 * @throws SecurityTxtTooManyRedirectsException
	 * @throws SecurityTxtNotFoundException
	 * @throws SecurityTxtCannotOpenUrlException
	 * @throws SecurityTxtNoHttpCodeException
	 * @throws SecurityTxtNoLocationHeaderException
	 * @throws SecurityTxtOnlyIpv6HostButIpv6DisabledException
	 * @throws SecurityTxtHostIpAddressNotPublicException
	 * @throws SecurityTxtHostIpAddressNotFoundException
	 * @throws SecurityTxtUrlUnsupportedSchemeException
	 * @throws SecurityTxtConnectedToWrongIpAddressException
	 * @throws SecurityTxtHostIpAddressInvalidException
	 * @throws SecurityTxtCannotOpenUrlUserAgentInvalidException
	 */
	public function check(Url $url, ?int $expiresWarningThreshold = null, bool $strictMode = false, bool $requireTopLevelLocation = false, bool $noIpv6 = false, ?int $maxAllowedRedirects = null): SecurityTxtCheckHostResult
	{
		$host = new SecurityTxtHost($this->urlParser->getBaseUrl($url));
		$this->callOnCallback($this->onHost, $host);
		$fetchResult = $this->fetcher->fetch($url, $requireTopLevelLocation, $noIpv6, $maxAllowedRedirects);
		$parseResult = $this->parser->parseFetchResult($fetchResult, $expiresWarningThreshold, $strictMode);

		foreach ($parseResult->getFetchErrors() as $error) {
			$this->violation($this->onFetchError, $error);
		}
		foreach ($parseResult->getLineErrors() as $line => $errors) {
			foreach ($errors as $error) {
				$this->violation($this->onLineError, $error, $line);
			}
		}
		foreach ($parseResult->getFileErrors() as $error) {
			$this->violation($this->onFileError, $error);
		}
		foreach ($parseResult->getFetchWarnings() as $warning) {
			$this->violation($this->onFetchWarning, $warning);
		}
		foreach ($parseResult->getLineWarnings() as $line => $warnings) {
			foreach ($warnings as $warning) {
				$this->violation($this->onLineWarning, $warning, $line);
			}
		}
		foreach ($parseResult->getFileWarnings() as $warning) {
			$this->violation($this->onFileWarning, $warning);
		}

		$expires = $parseResult->getSecurityTxt()->getExpires();
		if ($expires !== null) {
			$days = $expires->inDays();
			if ($expires->isExpired()) {
				$this->callOnCallback($this->onIsExpired, abs($days), $expires->getDateTime());
			} else {
				$this->callOnCallback($this->onExpires, $days, $expires->getDateTime());
			}
		}
		$signatureVerifyResult = $parseResult->getSecurityTxt()->getSignatureVerifyResult();
		if ($signatureVerifyResult !== null) {
			$this->callOnCallback($this->onValidSignature, $signatureVerifyResult->getKeyFingerprint(), $signatureVerifyResult->getDate());
		}

		return $this->resultFactory->create($host, $parseResult);
	}


	private function initFetcherCallbacks(): void
	{
		$this->fetcher->addOnUrl(
			function (Url $url): void {
				$this->callOnCallback($this->onUrl, $url);
			},
		);
		$this->fetcher->addOnFinalUrl(
			function (Url $url): void {
				$this->callOnCallback($this->onFinalUrl, $url);
			},
		);
		$this->fetcher->addOnRedirect(
			function (Url $url, Url $destination): void {
				$this->callOnCallback($this->onRedirect, $url, $destination);
			},
		);
		$this->fetcher->addOnUrlNotFound(
			function (Url $url): void {
				$this->callOnCallback($this->onUrlNotFound, $url);
			},
		);
	}


	/**
	 * @param list<callable(?int, SecurityTxtSpecViolation): void> $handlers
	 */
	private function violation(array $handlers, SecurityTxtSpecViolation $violation, ?int $line = null): void
	{
		$this->callOnCallback($handlers, $line, $violation);
	}


	/**
	 * @param list<callable> $onCallbacks
	 */
	private function callOnCallback(array $onCallbacks, string|int|DateTimeImmutable|Url|SecurityTxtHost|SecurityTxtSpecViolation|null ...$params): void
	{
		foreach ($onCallbacks as $onCallback) {
			$onCallback(...$params);
		}
	}


	/**
	 * @param callable(Url $url): void $onUrl
	 */
	public function addOnUrl(callable $onUrl): void
	{
		$this->onUrl[] = $onUrl;
	}


	/**
	 * @param callable(Url $url): void $onFinalUrl
	 */
	public function addOnFinalUrl(callable $onFinalUrl): void
	{
		$this->onFinalUrl[] = $onFinalUrl;
	}


	/**
	 * @param callable(Url $url, Url $destination): void $onRedirect
	 */
	public function addOnRedirect(callable $onRedirect): void
	{
		$this->onRedirect[] = $onRedirect;
	}


	/**
	 * @param callable(Url $url): void $onUrlNotFound
	 */
	public function addOnUrlNotFound(callable $onUrlNotFound): void
	{
		$this->onUrlNotFound[] = $onUrlNotFound;
	}


	/**
	 * @param callable(positive-int $daysAgo, DateTimeImmutable $expiryDate): void $onIsExpired
	 */
	public function addOnIsExpired(callable $onIsExpired): void
	{
		$this->onIsExpired[] = $onIsExpired;
	}


	/**
	 * @param callable(positive-int $inDays, DateTimeImmutable $expiryDate): void $onExpires
	 */
	public function addOnExpires(callable $onExpires): void
	{
		$this->onExpires[] = $onExpires;
	}


	/**
	 * @param callable(SecurityTxtHost $host): void $onParse
	 */
	public function addOnHost(callable $onParse): void
	{
		$this->onHost[] = $onParse;
	}


	/**
	 * @param callable(string $keyFingerprint, DateTimeImmutable $signatureDate): void $onValidSignature
	 */
	public function addOnValidSignature(callable $onValidSignature): void
	{
		$this->onValidSignature[] = $onValidSignature;
	}


	/**
	 * @param callable(?int $line, SecurityTxtSpecViolation $violation): void $onFetchError
	 */
	public function addOnFetchError(callable $onFetchError): void
	{
		$this->onFetchError[] = $onFetchError;
	}


	/**
	 * @param callable(?int $line, SecurityTxtSpecViolation $violation): void $onLineError
	 */
	public function addOnLineError(callable $onLineError): void
	{
		$this->onLineError[] = $onLineError;
	}


	/**
	 * @param callable(?int $line, SecurityTxtSpecViolation $violation): void $onFileError
	 */
	public function addOnFileError(callable $onFileError): void
	{
		$this->onFileError[] = $onFileError;
	}


	/**
	 * @param callable(?int $line, SecurityTxtSpecViolation $violation): void $onFetchWarning
	 */
	public function addOnFetchWarning(callable $onFetchWarning): void
	{
		$this->onFetchWarning[] = $onFetchWarning;
	}


	/**
	 * @param callable(?int $line, SecurityTxtSpecViolation $violation): void $onLineWarning
	 */
	public function addOnLineWarning(callable $onLineWarning): void
	{
		$this->onLineWarning[] = $onLineWarning;
	}


	/**
	 * @param callable(?int $line, SecurityTxtSpecViolation $violation): void $onFileWarning
	 */
	public function addOnFileWarning(callable $onFileWarning): void
	{
		$this->onFileWarning[] = $onFileWarning;
	}

}
