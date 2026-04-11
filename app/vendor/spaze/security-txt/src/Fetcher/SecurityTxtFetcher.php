<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

use LogicException;
use Spaze\SecurityTxt\Fetcher\DnsLookup\SecurityTxtDnsProvider;
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
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtUrlNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtUrlUnsupportedSchemeException;
use Spaze\SecurityTxt\Fetcher\HttpClients\SecurityTxtFetcherHttpClient;
use Spaze\SecurityTxt\Parser\SecurityTxtSplitLines;
use Spaze\SecurityTxt\Parser\SecurityTxtUrlParser;
use Spaze\SecurityTxt\SecurityTxtContentType;
use Spaze\SecurityTxt\Violations\SecurityTxtContentTypeInvalid;
use Spaze\SecurityTxt\Violations\SecurityTxtContentTypeWrongCharset;
use Spaze\SecurityTxt\Violations\SecurityTxtTopLevelDiffers;
use Spaze\SecurityTxt\Violations\SecurityTxtTopLevelPathOnly;
use Spaze\SecurityTxt\Violations\SecurityTxtWellKnownPathOnly;
use Uri\WhatWg\InvalidUrlException;
use Uri\WhatWg\Url;

final class SecurityTxtFetcher
{

	/** @var array<string, list<string>> */
	private array $redirects = [];

	/** @var list<callable(string): void> */
	private array $onUrl = [];

	/** @var list<callable(string): void> */
	private array $onFinalUrl = [];

	/** @var list<callable(string, string): void> */
	private array $onRedirect = [];

	/** @var list<callable(string): void> */
	private array $onUrlNotFound = [];


	/**
	 * @param non-negative-int $maxAllowedRedirects
	 */
	public function __construct(
		private readonly SecurityTxtFetcherHttpClient $httpClient,
		private readonly SecurityTxtUrlParser $urlParser,
		private readonly SecurityTxtSplitLines $splitLines,
		private readonly SecurityTxtDnsProvider $dnsLookupProvider,
		private readonly int $maxAllowedRedirects = 5,
	) {
		$this->validateMaxAllowedRedirects($this->maxAllowedRedirects);
	}


	/**
	 * @param non-negative-int|null $maxAllowedRedirects
	 * @throws SecurityTxtCannotOpenUrlException
	 * @throws SecurityTxtCannotOpenUrlExtensionNotLoadedException
	 * @throws SecurityTxtNotFoundException
	 * @throws SecurityTxtTooManyRedirectsException
	 * @throws SecurityTxtHostNotFoundException
	 * @throws SecurityTxtHostIpAddressNotPublicException
	 * @throws SecurityTxtNoHttpCodeException
	 * @throws SecurityTxtNoLocationHeaderException
	 * @throws SecurityTxtOnlyIpv6HostButIpv6DisabledException
	 * @throws SecurityTxtHostIpAddressNotFoundException
	 * @throws SecurityTxtUrlUnsupportedSchemeException
	 * @throws SecurityTxtCannotParseHostnameException
	 * @throws SecurityTxtConnectedToWrongIpAddressException
	 * @throws SecurityTxtHostIpAddressInvalidException
	 * @throws SecurityTxtCannotOpenUrlUserAgentInvalidException
	 */
	public function fetch(Url $url, bool $requireTopLevelLocation = false, bool $noIpv6 = false, ?int $maxAllowedRedirects = null): SecurityTxtFetchResult
	{
		$this->redirects = [];
		if ($maxAllowedRedirects !== null) {
			$this->validateMaxAllowedRedirects($maxAllowedRedirects);
		}
		$host = $url->getUnicodeHost();
		if ($host === null) {
			throw new SecurityTxtCannotParseHostnameException($url->toUnicodeString());
		}
		try {
			$baseUrl = $url
				->withUsername(null)
				->withPassword(null)
				->withScheme('https')
				->withQuery(null)
				->withFragment(null);
			$wellKnownUrl = $baseUrl->withPath('/.well-known/security.txt');
			$topLevelUrl = $baseUrl->withPath('/security.txt');
		} catch (InvalidUrlException $e) {
			throw new LogicException("Can't set URL components: {$e->getMessage()}", previous: $e);
		}
		$wellKnown = $this->fetchUrl($wellKnownUrl, $host, $noIpv6, $maxAllowedRedirects);
		$topLevel = $this->fetchUrl($topLevelUrl, $host, $noIpv6, $maxAllowedRedirects);
		return $this->getResult($wellKnown, $topLevel, $requireTopLevelLocation);
	}


	/**
	 * @param non-negative-int|null $maxAllowedRedirects
	 * @throws SecurityTxtTooManyRedirectsException
	 * @throws SecurityTxtHostNotFoundException
	 * @throws SecurityTxtHostIpAddressNotPublicException
	 * @throws SecurityTxtCannotOpenUrlException
	 * @throws SecurityTxtCannotOpenUrlExtensionNotLoadedException
	 * @throws SecurityTxtNotFoundException
	 * @throws SecurityTxtNoHttpCodeException
	 * @throws SecurityTxtNoLocationHeaderException
	 * @throws SecurityTxtOnlyIpv6HostButIpv6DisabledException
	 * @throws SecurityTxtHostIpAddressNotFoundException
	 * @throws SecurityTxtUrlUnsupportedSchemeException
	 * @throws SecurityTxtCannotParseHostnameException
	 * @throws SecurityTxtConnectedToWrongIpAddressException
	 * @throws SecurityTxtHostIpAddressInvalidException
	 * @throws SecurityTxtCannotOpenUrlUserAgentInvalidException
	 */
	private function fetchUrl(Url $url, string $host, bool $noIpv6, ?int $maxAllowedRedirects): SecurityTxtFetcherFetchHostResult
	{
		$finalUrl = $url->toUnicodeString();
		$this->callOnCallback($this->onUrl, $url->toUnicodeString());
		try {
			$response = $this->getResponse(new SecurityTxtFetcherUrl($url, $this->getRedirects($url)), $host, $url, $finalUrl, $noIpv6, $maxAllowedRedirects);
			$ipAddress = $response->getIpAddress();
			$ipAddressType = $response->getIpAddressType();
		} catch (SecurityTxtUrlNotFoundException $e) {
			$this->callOnCallback($this->onUrlNotFound, $e->getUrl());
			$response = null;
			$ipAddress = $e->getIpAddress();
			$ipAddressType = SecurityTxtIpAddressType::from($e->getIpAddressType());
		}
		return new SecurityTxtFetcherFetchHostResult(
			$url->toUnicodeString(),
			$finalUrl,
			$ipAddress,
			$ipAddressType,
			isset($e) ? $e->getCode() : 200,
			$response,
		);
	}


	/**
	 * @param non-negative-int|null $maxAllowedRedirects
	 * @throws SecurityTxtTooManyRedirectsException
	 * @throws SecurityTxtNotFoundException
	 * @throws SecurityTxtCannotOpenUrlException
	 * @throws SecurityTxtCannotOpenUrlExtensionNotLoadedException
	 * @throws SecurityTxtUrlNotFoundException
	 * @throws SecurityTxtHostIpAddressNotFoundException
	 * @throws SecurityTxtHostNotFoundException
	 * @throws SecurityTxtOnlyIpv6HostButIpv6DisabledException
	 * @throws SecurityTxtHostIpAddressNotPublicException
	 * @throws SecurityTxtCannotParseHostnameException
	 * @throws SecurityTxtHostIpAddressInvalidException
	 * @throws SecurityTxtNoHttpCodeException
	 * @throws SecurityTxtNoLocationHeaderException
	 * @throws SecurityTxtUrlUnsupportedSchemeException
	 * @throws SecurityTxtConnectedToWrongIpAddressException
	 * @throws SecurityTxtCannotOpenUrlUserAgentInvalidException
	 */
	private function getResponse(SecurityTxtFetcherUrl $url, string $host, Url $originalUrl, string &$finalUrl, bool $noIpv6, ?int $maxAllowedRedirects): SecurityTxtFetcherResponse
	{
		$ipRecord = $ipv6Record = null;
		if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
			$ipRecord = $host;
		} else {
			if (preg_match('/^\[(.*)]$/', $host, $matches) === 1) {
				$hostIpv6 = $matches[1];
				if (filter_var($hostIpv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
					$ipv6Record = $hostIpv6;
				}
			}
		}
		if ($ipRecord === null && $ipv6Record === null) {
			$dnsRecords = $this->dnsLookupProvider->getRecords($url->getUrl(), $host);
			$ipRecord = $dnsRecords->getIpRecord();
			$ipv6Record = $dnsRecords->getIpv6Record();
		}
		if ($noIpv6 && $ipv6Record !== null && $ipRecord === null) {
			throw new SecurityTxtOnlyIpv6HostButIpv6DisabledException($host, $ipv6Record, $url->getUrl()->toUnicodeString());
		}
		if (!$noIpv6 && $ipv6Record !== null) {
			$ipAddress = $ipv6Record;
			$ipAddressType = SecurityTxtIpAddressType::V6;
		} elseif ($ipRecord !== null) {
			$ipAddress = $ipRecord;
			$ipAddressType = SecurityTxtIpAddressType::V4;
		}
		if (!isset($ipAddress) || !isset($ipAddressType)) {
			throw new SecurityTxtHostIpAddressNotFoundException($url->getUrl()->toUnicodeString(), $host);
		}
		$this->validateIpAddress($ipAddress, $ipAddressType, $host, $url);

		$response = $this->httpClient->getResponse($url, $host, $ipAddress, $ipAddressType);
		if ($response->getHttpCode() >= 400) {
			throw new SecurityTxtUrlNotFoundException($url->getUrl()->toUnicodeString(), $response->getHttpCode(), $ipAddress, $ipAddressType->value);
		}
		if ($response->getHttpCode() >= 300) {
			return $this->redirect($url->getUrl(), $originalUrl, $response, $finalUrl, $noIpv6, $maxAllowedRedirects);
		}
		return $response;
	}


	/**
	 * @throws SecurityTxtHostIpAddressInvalidException
	 * @throws SecurityTxtHostIpAddressNotPublicException
	 */
	private function validateIpAddress(string $ipAddress, SecurityTxtIpAddressType $type, string $host, SecurityTxtFetcherUrl $url): void
	{
		$flag = $type === SecurityTxtIpAddressType::V4 ? FILTER_FLAG_IPV4 : FILTER_FLAG_IPV6;
		if (filter_var($ipAddress, FILTER_VALIDATE_IP, $flag) === false) {
			throw new SecurityTxtHostIpAddressInvalidException($host, $ipAddress, $type->value, $url->getUrl()->toUnicodeString());
		}
		if (filter_var($ipAddress, FILTER_VALIDATE_IP, $flag | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_GLOBAL_RANGE) === false) {
			throw new SecurityTxtHostIpAddressNotPublicException($host, $ipAddress, $url->getUrl()->toUnicodeString());
		}
	}


	/**
	 * @throws SecurityTxtNotFoundException
	 */
	private function getResult(SecurityTxtFetcherFetchHostResult $wellKnown, SecurityTxtFetcherFetchHostResult $topLevel, bool $requireTopLevelLocation): SecurityTxtFetchResult
	{
		$errors = $warnings = [];
		$wellKnownContents = $wellKnown->isRegularHtmlPage() || $wellKnown->isTruncated() ? null : $wellKnown->getContents();
		$topLevelContents = $topLevel->isRegularHtmlPage() || $topLevel->isTruncated() ? null : $topLevel->getContents();
		if ($wellKnownContents === null && $topLevelContents === null) {
			throw new SecurityTxtNotFoundException(
				[
					$wellKnown->getUrl() => [
						'ip' => $wellKnown->getIpAddress(),
						'type' => $wellKnown->getIpAddressType()->value,
						'code' => $wellKnown->getHttpCode(),
						'redirects' => $this->redirects[$wellKnown->getUrl()] ?? [],
						'html' => $wellKnown->isRegularHtmlPage(),
						'truncated' => $wellKnown->isTruncated(),
					],
					$topLevel->getUrl() => [
						'ip' => $topLevel->getIpAddress(),
						'type' => $topLevel->getIpAddressType()->value,
						'code' => $topLevel->getHttpCode(),
						'redirects' => $this->redirects[$topLevel->getUrl()] ?? [],
						'html' => $topLevel->isRegularHtmlPage(),
						'truncated' => $topLevel->isTruncated(),
					],
				],
				$wellKnown->getUrl(),
			);
		} elseif ($wellKnownContents !== null && $topLevelContents === null) {
			if ($requireTopLevelLocation) {
				$warnings[] = new SecurityTxtWellKnownPathOnly();
			}
			$result = $wellKnown;
			$contents = $wellKnownContents;
		} elseif ($wellKnownContents === null) {
			$errors[] = new SecurityTxtTopLevelPathOnly();
			$result = $topLevel;
			$contents = $topLevelContents;
		} elseif ($wellKnownContents !== $topLevelContents) {
			if ($topLevelContents === null) {
				throw new LogicException('This should not happen');
			}
			if ($wellKnown->getFinalUrl() !== $topLevel->getFinalUrl()) {
				$warnings[] = new SecurityTxtTopLevelDiffers($wellKnownContents, $topLevelContents);
			}
			$result = $wellKnown;
			$contents = $wellKnownContents;
		} else {
			$result = $wellKnown;
			$contents = $wellKnownContents;
		}
		$this->callOnCallback($this->onFinalUrl, $result->getFinalUrl());

		$contentTypeHeader = $result->getContentType();
		if ($contentTypeHeader === null || $contentTypeHeader->getLowercaseContentType() !== SecurityTxtContentType::CONTENT_TYPE) {
			$errors[] = new SecurityTxtContentTypeInvalid($result->getUrl(), $contentTypeHeader?->getContentType());
		} elseif ($contentTypeHeader->getLowercaseCharsetParameter() !== SecurityTxtContentType::CHARSET_PARAMETER) {
			$errors[] = new SecurityTxtContentTypeWrongCharset($result->getUrl(), $contentTypeHeader->getContentType(), $contentTypeHeader->getCharsetParameter());
		}
		return new SecurityTxtFetchResult(
			$result->getUrl(),
			$result->getFinalUrl(),
			$this->redirects,
			$contents,
			$result->isTruncated(),
			$this->splitLines->splitLines($contents),
			$errors,
			$warnings,
		);
	}


	/**
	 * @param list<callable> $onCallbacks
	 */
	private function callOnCallback(array $onCallbacks, string ...$params): void
	{
		foreach ($onCallbacks as $onCallback) {
			$onCallback(...$params);
		}
	}


	/**
	 * @param callable(string): void $onUrl
	 */
	public function addOnUrl(callable $onUrl): void
	{
		$this->onUrl[] = $onUrl;
	}


	/**
	 * @param callable(string): void $onFinalUrl
	 */
	public function addOnFinalUrl(callable $onFinalUrl): void
	{
		$this->onFinalUrl[] = $onFinalUrl;
	}


	/**
	 * @param callable(string, string): void $onRedirect
	 */
	public function addOnRedirect(callable $onRedirect): void
	{
		$this->onRedirect[] = $onRedirect;
	}


	/**
	 * @param callable(string): void $onUrlNotFound
	 */
	public function addOnUrlNotFound(callable $onUrlNotFound): void
	{
		$this->onUrlNotFound[] = $onUrlNotFound;
	}


	/**
	 * @param non-negative-int|null $maxAllowedRedirects
	 * @throws SecurityTxtCannotOpenUrlException
	 * @throws SecurityTxtCannotOpenUrlExtensionNotLoadedException
	 * @throws SecurityTxtConnectedToWrongIpAddressException
	 * @throws SecurityTxtHostIpAddressInvalidException
	 * @throws SecurityTxtHostIpAddressNotPublicException
	 * @throws SecurityTxtHostIpAddressNotFoundException
	 * @throws SecurityTxtHostNotFoundException
	 * @throws SecurityTxtOnlyIpv6HostButIpv6DisabledException
	 * @throws SecurityTxtNoHttpCodeException
	 * @throws SecurityTxtNoLocationHeaderException
	 * @throws SecurityTxtNotFoundException
	 * @throws SecurityTxtTooManyRedirectsException
	 * @throws SecurityTxtUrlNotFoundException
	 * @throws SecurityTxtUrlUnsupportedSchemeException
	 * @throws SecurityTxtCannotParseHostnameException
	 * @throws SecurityTxtCannotOpenUrlUserAgentInvalidException
	 */
	private function redirect(Url $url, Url $originalUrl, SecurityTxtFetcherResponse $response, string &$finalUrl, bool $noIpv6, ?int $maxAllowedRedirects): SecurityTxtFetcherResponse
	{
		if ($maxAllowedRedirects === null) {
			$maxAllowedRedirects = $this->maxAllowedRedirects;
		}
		$location = $response->getHeader('Location');
		if ($location === null) {
			throw new SecurityTxtNoLocationHeaderException($url->toUnicodeString(), $response->getHttpCode());
		} else {
			$originalUrlString = $originalUrl->toUnicodeString();
			$previousUrl = isset($this->redirects[$originalUrlString]) && $this->redirects[$originalUrlString] !== [] ? $this->redirects[$originalUrlString][array_key_last($this->redirects[$originalUrlString])] : $originalUrlString;
			$this->callOnCallback($this->onRedirect, $previousUrl, $location);
			$this->redirects[$originalUrlString][] = $location;
			$locationUrl = $this->urlParser->getRedirectUrl($location, $url);
			$finalUrl = $locationUrl->toUnicodeString();
			if (count($this->redirects[$originalUrlString]) > $maxAllowedRedirects) {
				throw new SecurityTxtTooManyRedirectsException($url->toUnicodeString(), $this->redirects[$originalUrlString], $maxAllowedRedirects);
			}
			$locationHost = $locationUrl->getUnicodeHost();
			if ($locationHost === null) {
				throw new SecurityTxtCannotParseHostnameException($locationUrl->toUnicodeString());
			}
			return $this->getResponse(new SecurityTxtFetcherUrl($locationUrl, $this->getRedirects($originalUrl)), $locationHost, $originalUrl, $finalUrl, $noIpv6, $maxAllowedRedirects);
		}
	}


	/**
	 * @return list<string>
	 */
	private function getRedirects(Url $url): array
	{
		$urlString = $url->toUnicodeString();
		$redirects = $this->redirects[$urlString] ?? [];
		if ($redirects !== []) {
			array_unshift($redirects, $urlString);
		}
		return $redirects;
	}


	private function validateMaxAllowedRedirects(int $maxAllowedRedirects): void
	{
		if ($maxAllowedRedirects < 0) {
			throw new LogicException('maxAllowedRedirects must be greater than or equal to 0 (0 means no redirects allowed)');
		}
	}

}
