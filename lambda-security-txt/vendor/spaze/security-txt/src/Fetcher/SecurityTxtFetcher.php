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
use Spaze\SecurityTxt\SecurityTxtHost;
use Spaze\SecurityTxt\SecurityTxtPrintableValue;
use Spaze\SecurityTxt\Violations\SecurityTxtContentTypeInvalid;
use Spaze\SecurityTxt\Violations\SecurityTxtContentTypeWrongCharset;
use Spaze\SecurityTxt\Violations\SecurityTxtTopLevelDiffers;
use Spaze\SecurityTxt\Violations\SecurityTxtTopLevelPathOnly;
use Spaze\SecurityTxt\Violations\SecurityTxtWellKnownPathOnly;
use Uri\UriComparisonMode;
use Uri\WhatWg\InvalidUrlException;
use Uri\WhatWg\Url;

final class SecurityTxtFetcher
{

	/** @var array<string, SecurityTxtRedirects> Keyed by the URL a chain started at, which has to be a string; what the chain holds does not */
	private array $redirects = [];

	/** @var list<callable(Url): void> */
	private array $onUrl = [];

	/** @var list<callable(Url): void> */
	private array $onFinalUrl = [];

	/** @var list<callable(Url, Url): void> */
	private array $onRedirect = [];

	/** @var list<callable(Url): void> */
	private array $onUrlNotFound = [];


	/**
	 * @param non-negative-int $maxAllowedRedirects
	 */
	public function __construct(
		private readonly SecurityTxtFetcherHttpClient $httpClient,
		private readonly SecurityTxtUrlParser $urlParser,
		private readonly SecurityTxtSplitLines $splitLines,
		private readonly SecurityTxtDnsProvider $dnsLookupProvider,
		private readonly SecurityTxtIpAddressValidator $ipAddressValidator,
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
		$baseUrl = $this->urlParser->getBaseUrl($url);
		$host = new SecurityTxtHost($baseUrl);
		try {
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
	private function fetchUrl(Url $url, SecurityTxtHost $host, bool $noIpv6, ?int $maxAllowedRedirects): SecurityTxtFetcherFetchHostResult
	{
		$finalUrl = $url;
		$this->callOnCallback($this->onUrl, $url);
		try {
			$response = $this->getResponse(new SecurityTxtFetcherUrl($url, $this->getRedirects($url)), $host, $url, $finalUrl, $noIpv6, $maxAllowedRedirects);
			$ipAddress = $response->getIpAddress();
			$ipAddressType = $response->getIpAddressType();
			$httpCode = $response->getHttpCode();
		} catch (SecurityTxtUrlNotFoundException $e) {
			$this->callOnCallback($this->onUrlNotFound, $finalUrl);
			$response = null;
			$ipAddress = $e->getIpAddress();
			$ipAddressType = $e->getIpAddressType();
			$httpCode = $e->getCode();
		}
		return new SecurityTxtFetcherFetchHostResult(
			$url,
			$finalUrl,
			$ipAddress,
			$ipAddressType,
			$httpCode,
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
	private function getResponse(SecurityTxtFetcherUrl $url, SecurityTxtHost $host, Url $originalUrl, Url &$finalUrl, bool $noIpv6, ?int $maxAllowedRedirects): SecurityTxtFetcherResponse
	{
		$ipRecord = $ipv6Record = null;
		if (filter_var($host->getAscii(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
			$ipRecord = $host->getAscii();
		} else {
			if (preg_match('/^\[(.*)]$/', $host->getAscii(), $matches) === 1) {
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
			throw new SecurityTxtOnlyIpv6HostButIpv6DisabledException($host, $ipv6Record, $url->getUrl());
		}
		if (!$noIpv6 && $ipv6Record !== null) {
			$ipAddress = $ipv6Record;
			$ipAddressType = SecurityTxtIpAddressType::V6;
		} elseif ($ipRecord !== null) {
			$ipAddress = $ipRecord;
			$ipAddressType = SecurityTxtIpAddressType::V4;
		}
		if (!isset($ipAddress) || !isset($ipAddressType)) {
			throw new SecurityTxtHostIpAddressNotFoundException($url->getUrl(), $host);
		}
		$this->ipAddressValidator->validate($ipAddress, $ipAddressType, $host, $url->getUrl());

		$response = $this->httpClient->getResponse($url, $host, $ipAddress, $ipAddressType);
		if ($response->getHttpCode() >= 400) {
			throw new SecurityTxtUrlNotFoundException($url->getUrl(), $response->getHttpCode(), $ipAddress, $ipAddressType);
		}
		if ($response->getHttpCode() >= 300) {
			return $this->redirect($url->getUrl(), $originalUrl, $response, $finalUrl, $noIpv6, $maxAllowedRedirects);
		}
		return $response;
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
			$wellKnownUrl = new SecurityTxtPrintableValue($wellKnown->getUrl())->render();
			$topLevelUrl = new SecurityTxtPrintableValue($topLevel->getUrl())->render();
			// The `'type' => ...->value` and the spelled out chains below are scalar on purpose: unlike the exceptions that take a case, a `Url` or a chain itself,
			// `SecurityTxtNotFoundException` reads this array back with `is_int()` and `is_string()`, being the shape a stored result carries and replays from. Nothing
			// catches one left as an object, the shape is `mixed` to the analysers
			throw new SecurityTxtNotFoundException(
				[
					$wellKnownUrl => [
						'ip' => $wellKnown->getIpAddress(),
						'type' => $wellKnown->getIpAddressType()->value,
						'code' => $wellKnown->getHttpCode(),
						'redirects' => ($this->redirects[$wellKnownUrl] ?? new SecurityTxtRedirects())->toStrings(),
						'html' => $wellKnown->isRegularHtmlPage(),
						'truncated' => $wellKnown->isTruncated(),
					],
					$topLevelUrl => [
						'ip' => $topLevel->getIpAddress(),
						'type' => $topLevel->getIpAddressType()->value,
						'code' => $topLevel->getHttpCode(),
						'redirects' => ($this->redirects[$topLevelUrl] ?? new SecurityTxtRedirects())->toStrings(),
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
			// `equals()` ignores the fragment unless told otherwise, and a host picks the fragment of a final URL through its `Location` header
			if (!$wellKnown->getFinalUrl()->equals($topLevel->getFinalUrl(), UriComparisonMode::IncludeFragment)) {
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
		// The URL that answered, not the one the check was built from: a header comes from a response, and after a redirect that response is somewhere else
		if ($contentTypeHeader === null || $contentTypeHeader->getLowercaseContentType() !== SecurityTxtContentType::CONTENT_TYPE) {
			$errors[] = new SecurityTxtContentTypeInvalid($result->getFinalUrl(), $contentTypeHeader?->getContentType());
		} elseif ($contentTypeHeader->getLowercaseCharsetParameter() !== SecurityTxtContentType::CHARSET_PARAMETER) {
			$errors[] = new SecurityTxtContentTypeWrongCharset($result->getFinalUrl(), $contentTypeHeader->getContentType(), $contentTypeHeader->getCharsetParameter());
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
	private function callOnCallback(array $onCallbacks, Url ...$params): void
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
	private function redirect(Url $url, Url $originalUrl, SecurityTxtFetcherResponse $response, Url &$finalUrl, bool $noIpv6, ?int $maxAllowedRedirects): SecurityTxtFetcherResponse
	{
		if ($maxAllowedRedirects === null) {
			$maxAllowedRedirects = $this->maxAllowedRedirects;
		}
		$location = $response->getHeader('Location');
		if ($location === null) {
			throw new SecurityTxtNoLocationHeaderException($url, $response->getHttpCode());
		} else {
			$originalUrlString = new SecurityTxtPrintableValue($originalUrl)->render();
			$locationUrl = $this->urlParser->getRedirectUrl($location, $url);
			$this->callOnCallback($this->onRedirect, $url, $locationUrl);
			// Where the redirect led rather than the header that said so: a `Location` can be relative, or spell a host in punycode, and this is a record of the URLs a check
			// went to and not of what a server typed
			$this->redirects[$originalUrlString] = ($this->redirects[$originalUrlString] ?? new SecurityTxtRedirects())->withRedirect($locationUrl);
			$finalUrl = $locationUrl;
			if ($this->redirects[$originalUrlString]->count() > $maxAllowedRedirects) {
				throw new SecurityTxtTooManyRedirectsException($url, $this->redirects[$originalUrlString], $maxAllowedRedirects);
			}
			// The URL is built first on purpose: its constructor is where an unsupported scheme is refused, and a scheme with no host at all would otherwise be reported as
			// a hostname that will not parse, losing the redirect chain that says where the host sent us. Settling comes after it for the same reason
			$fetcherUrl = new SecurityTxtFetcherUrl($locationUrl, $this->getRedirects($originalUrl));
			$settledUrl = $this->urlParser->normalize($fetcherUrl->getUrl());
			return $this->getResponse(new SecurityTxtFetcherUrl($settledUrl, $this->getRedirects($originalUrl)), new SecurityTxtHost($settledUrl), $originalUrl, $settledUrl, $noIpv6, $maxAllowedRedirects);
		}
	}


	/**
	 * A chain read back starts at the URL it was asked for, which is not a redirect and so is not recorded as one.
	 */
	private function getRedirects(Url $url): SecurityTxtRedirects
	{
		$urlString = new SecurityTxtPrintableValue($url)->render();
		$redirects = $this->redirects[$urlString] ?? null;
		return $redirects === null ? new SecurityTxtRedirects() : new SecurityTxtRedirects($urlString, ...$redirects->toStrings());
	}


	private function validateMaxAllowedRedirects(int $maxAllowedRedirects): void
	{
		if ($maxAllowedRedirects < 0) {
			throw new LogicException('maxAllowedRedirects must be greater than or equal to 0 (0 means no redirects allowed)');
		}
	}

}
