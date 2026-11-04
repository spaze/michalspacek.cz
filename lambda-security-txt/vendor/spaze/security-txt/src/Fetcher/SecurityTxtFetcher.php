<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

use LogicException;
use Spaze\SecurityTxt\Fetcher\DnsLookup\SecurityTxtDnsProvider;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlContentEncodingException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlExtensionNotLoadedException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlUserAgentInvalidException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotParseHostnameException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtConnectedToWrongIpAddressException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressInvalidException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressNotPublicException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNoHttpCodeException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNoLocationHeaderException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtOnlyIpv6HostButIpv6DisabledException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtRedirectLoopException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtRedirectUrlTooLongException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtTooManyRedirectsException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtUrlNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtUrlUnsupportedSchemeException;
use Spaze\SecurityTxt\Fetcher\HttpClients\SecurityTxtFetcherHttpClient;
use Spaze\SecurityTxt\Parser\SecurityTxtSplitLines;
use Spaze\SecurityTxt\Parser\SecurityTxtUrlParser;
use Spaze\SecurityTxt\SecurityTxtContentType;
use Spaze\SecurityTxt\SecurityTxtHost;
use Spaze\SecurityTxt\SecurityTxtPhpUrlWorkarounds;
use Spaze\SecurityTxt\SecurityTxtPrintableValue;
use Spaze\SecurityTxt\Violations\SecurityTxtContentTypeInvalid;
use Spaze\SecurityTxt\Violations\SecurityTxtContentTypeWrongCharset;
use Spaze\SecurityTxt\Violations\SecurityTxtRedirectNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtTopLevelDiffers;
use Spaze\SecurityTxt\Violations\SecurityTxtTopLevelPathOnly;
use Spaze\SecurityTxt\Violations\SecurityTxtWellKnownPathOnly;
use Uri\UriComparisonMode;
use Uri\WhatWg\InvalidUrlException;
use Uri\WhatWg\Url;

final class SecurityTxtFetcher
{
	private const int DEFAULT_MAX_ALLOWED_REDIRECTS = 5;
	private const int MAX_REDIRECT_URL_LENGTH = 1000;

	/** @var array<string, SecurityTxtRedirects> Keyed by the URL a chain started at, which has to be a string; what the chain holds does not */
	private array $redirects = [];

	/** @var list<callable(Url): void> */
	private array $onUrl = [];

	/** @var list<callable(Url): void> */
	private array $onFinalUrl = [];

	/** @var list<callable(Url, Url): void> */
	private array $onRedirect = [];

	/** @var list<callable(Url, int): void> */
	private array $onUrlNotFound = [];

	/** @var list<callable(SecurityTxtFetcherException): void> */
	private array $onUrlFailed = [];

	/** @var non-negative-int */
	private readonly int $maxAllowedRedirects;


	/**
	 * @param non-negative-int|null $maxAllowedRedirects Pass null to use the built-in default (5)
	 */
	public function __construct(
		private readonly SecurityTxtFetcherHttpClient $httpClient,
		private readonly SecurityTxtUrlParser $urlParser,
		private readonly SecurityTxtSplitLines $splitLines,
		private readonly SecurityTxtDnsProvider $dnsLookupProvider,
		private readonly SecurityTxtIpAddressValidator $ipAddressValidator,
		?int $maxAllowedRedirects = null,
	) {
		$this->maxAllowedRedirects = $maxAllowedRedirects ?? self::DEFAULT_MAX_ALLOWED_REDIRECTS;
		$this->validateMaxAllowedRedirects($this->maxAllowedRedirects);
	}


	/**
	 * @param non-negative-int|null $maxAllowedRedirects
	 * @throws SecurityTxtCannotOpenUrlException
	 * @throws SecurityTxtCannotOpenUrlContentEncodingException
	 * @throws SecurityTxtCannotOpenUrlExtensionNotLoadedException
	 * @throws SecurityTxtNotFoundException
	 * @throws SecurityTxtTooManyRedirectsException
	 * @throws SecurityTxtRedirectLoopException
	 * @throws SecurityTxtRedirectUrlTooLongException
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
		try {
			$wellKnown = $this->fetchUrl($wellKnownUrl, $host, $noIpv6, $maxAllowedRedirects);
		} catch (SecurityTxtFetcherException $e) {
			$wellKnown = $e;
		}
		try {
			$topLevel = $this->fetchUrl($topLevelUrl, $host, $noIpv6, $maxAllowedRedirects);
		} catch (SecurityTxtFetcherException $e) {
			$topLevel = $e;
		}
		return $this->getResult($wellKnown, $topLevel, $requireTopLevelLocation);
	}


	/**
	 * @param non-negative-int|null $maxAllowedRedirects
	 * @throws SecurityTxtTooManyRedirectsException
	 * @throws SecurityTxtRedirectLoopException
	 * @throws SecurityTxtRedirectUrlTooLongException
	 * @throws SecurityTxtHostNotFoundException
	 * @throws SecurityTxtHostIpAddressNotPublicException
	 * @throws SecurityTxtCannotOpenUrlException
	 * @throws SecurityTxtCannotOpenUrlContentEncodingException
	 * @throws SecurityTxtCannotOpenUrlExtensionNotLoadedException
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
		$this->callOnCallback($this->onUrl, $url);
		$fetcherUrl = new SecurityTxtFetcherUrl($url, $this->getRedirects($url));
		try {
			$response = $this->getResponse($fetcherUrl, $host, $noIpv6);
			while ($response->getHttpCode() >= 300) {
				$fetcherUrl = $this->redirect($fetcherUrl->getUrl(), $url, $response, $maxAllowedRedirects);
				$response = $this->getResponse($fetcherUrl, new SecurityTxtHost($fetcherUrl->getUrl()), $noIpv6);
			}
			$ipAddress = $response->getIpAddress();
			$ipAddressType = $response->getIpAddressType();
			$httpCode = $response->getHttpCode();
		} catch (SecurityTxtUrlNotFoundException $e) {
			$this->callOnCallback($this->onUrlNotFound, $fetcherUrl->getUrl(), $e->getCode());
			$response = null;
			$ipAddress = $e->getIpAddress();
			$ipAddressType = $e->getIpAddressType();
			$httpCode = $e->getCode();
		}
		return new SecurityTxtFetcherFetchHostResult(
			$url,
			$fetcherUrl->getUrl(),
			$ipAddress,
			$ipAddressType,
			$httpCode,
			$response,
		);
	}


	/**
	 * @throws SecurityTxtCannotOpenUrlException
	 * @throws SecurityTxtCannotOpenUrlContentEncodingException
	 * @throws SecurityTxtCannotOpenUrlExtensionNotLoadedException
	 * @throws SecurityTxtUrlNotFoundException
	 * @throws SecurityTxtHostIpAddressNotFoundException
	 * @throws SecurityTxtHostNotFoundException
	 * @throws SecurityTxtOnlyIpv6HostButIpv6DisabledException
	 * @throws SecurityTxtHostIpAddressNotPublicException
	 * @throws SecurityTxtHostIpAddressInvalidException
	 * @throws SecurityTxtNoHttpCodeException
	 * @throws SecurityTxtConnectedToWrongIpAddressException
	 * @throws SecurityTxtCannotOpenUrlUserAgentInvalidException
	 */
	private function getResponse(SecurityTxtFetcherUrl $url, SecurityTxtHost $host, bool $noIpv6): SecurityTxtFetcherResponse
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
			try {
				$dnsRecords = $this->dnsLookupProvider->getRecords($url->getUrl(), $host);
			} catch (SecurityTxtHostNotFoundException $e) {
				// The provider is asked about one URL and knows nothing of the redirects that led there, which are what makes a host nobody typed make sense
				throw new SecurityTxtHostNotFoundException($url->getUrl(), $host, $url->getRedirects(), $e);
			}
			$ipRecord = $dnsRecords->getIpRecord();
			$ipv6Record = $dnsRecords->getIpv6Record();
		}
		if ($noIpv6 && $ipv6Record !== null && $ipRecord === null) {
			throw new SecurityTxtOnlyIpv6HostButIpv6DisabledException($host, $ipv6Record, $url->getUrl(), $url->getRedirects());
		}
		if (!$noIpv6 && $ipv6Record !== null) {
			$ipAddress = $ipv6Record;
			$ipAddressType = SecurityTxtIpAddressType::V6;
		} elseif ($ipRecord !== null) {
			$ipAddress = $ipRecord;
			$ipAddressType = SecurityTxtIpAddressType::V4;
		}
		if (!isset($ipAddress) || !isset($ipAddressType)) {
			throw new SecurityTxtHostIpAddressNotFoundException($url->getUrl(), $host, $url->getRedirects());
		}
		$this->ipAddressValidator->validate($ipAddress, $ipAddressType, $host, $url->getUrl(), $url->getRedirects());

		$response = $this->httpClient->getResponse($url, $host, $ipAddress, $ipAddressType);
		if ($response->getHttpCode() >= 400) {
			throw new SecurityTxtUrlNotFoundException($url->getUrl(), $response->getHttpCode(), $ipAddress, $ipAddressType);
		}
		return $response;
	}


	/**
	 * @throws SecurityTxtNotFoundException
	 * @throws SecurityTxtFetcherException
	 */
	private function getResult(
		SecurityTxtFetcherFetchHostResult|SecurityTxtFetcherException $wellKnown,
		SecurityTxtFetcherFetchHostResult|SecurityTxtFetcherException $topLevel,
		bool $requireTopLevelLocation,
	): SecurityTxtFetchResult {
		$errors = $warnings = [];
		$wellKnownContents = $wellKnown instanceof SecurityTxtFetcherException || $wellKnown->isRegularHtmlPage() || $wellKnown->isTruncated() ? null : $wellKnown->getContents();
		$topLevelContents = $topLevel instanceof SecurityTxtFetcherException || $topLevel->isRegularHtmlPage() || $topLevel->isTruncated() ? null : $topLevel->getContents();
		if ($wellKnown instanceof SecurityTxtFetcherFetchHostResult && $wellKnownContents !== null) {
			if ($topLevel instanceof SecurityTxtFetcherException) {
				$this->callOnCallback($this->onUrlFailed, $topLevel);
			}
			if ($topLevel instanceof SecurityTxtFetcherException || $topLevelContents === null) {
				if ($requireTopLevelLocation) {
					$warnings[] = new SecurityTxtWellKnownPathOnly($topLevel instanceof SecurityTxtFetcherException ? $topLevel : null);
				}
			} elseif ($wellKnownContents !== $topLevelContents) {
				// `equals()` ignores the fragment unless told otherwise, and a host picks the fragment of a final URL through its `Location` header
				if (!$wellKnown->getFinalUrl()->equals($topLevel->getFinalUrl(), UriComparisonMode::IncludeFragment)) {
					$warnings[] = new SecurityTxtTopLevelDiffers($wellKnownContents, $topLevelContents);
				}
			}
			$result = $wellKnown;
			$contents = $wellKnownContents;
		} elseif ($topLevel instanceof SecurityTxtFetcherFetchHostResult && $topLevelContents !== null) {
			if ($wellKnown instanceof SecurityTxtFetcherException) {
				$this->callOnCallback($this->onUrlFailed, $wellKnown);
			}
			// A code other than one saying there is no file there, a 403 from a firewall say, is what needs fixing more than where the file is
			$wellKnownHttpCode = $wellKnown instanceof SecurityTxtFetcherFetchHostResult ? $wellKnown->getHttpCode() : null;
			$errors[] = new SecurityTxtTopLevelPathOnly(
				$wellKnownHttpCode !== null && $wellKnownHttpCode >= 400 && $wellKnownHttpCode !== 404 && $wellKnownHttpCode !== 410 ? $wellKnownHttpCode : null,
				$wellKnown instanceof SecurityTxtFetcherException ? $wellKnown : null,
			);
			$result = $topLevel;
			$contents = $topLevelContents;
		} else {
			// A location that fails has no file there, so a failure is what gets reported only without a file at either location, the /.well-known/ one's first as RFC 9116
			// has that location used
			if ($wellKnown instanceof SecurityTxtFetcherException) {
				throw $wellKnown;
			}
			if ($topLevel instanceof SecurityTxtFetcherException) {
				throw $topLevel;
			}
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
		}
		$this->callOnCallback($this->onFinalUrl, $result->getFinalUrl());

		// The last hop is the final URL, and the file location check is what reports that one
		$redirects = $this->redirects[new SecurityTxtPrintableValue($result->getUrl())->render()] ?? new SecurityTxtRedirects();
		foreach (array_slice($redirects->toStrings(), 0, -1) as $redirect) {
			$redirectUrl = Url::parse($redirect);
			if ($redirectUrl !== null && $redirectUrl->getScheme() === 'http') {
				$errors[] = new SecurityTxtRedirectNotHttps($result->getUrl(), $redirectUrl);
				break;
			}
		}

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
	private function callOnCallback(array $onCallbacks, Url|int|SecurityTxtFetcherException ...$params): void
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
	 * @param callable(Url $url, int $httpCode): void $onUrlNotFound
	 */
	public function addOnUrlNotFound(callable $onUrlNotFound): void
	{
		$this->onUrlNotFound[] = $onUrlNotFound;
	}


	/**
	 * Called only when the file at the other location is used, a failure that leaves no file anywhere is thrown instead
	 *
	 * @param callable(SecurityTxtFetcherException $exception): void $onUrlFailed
	 */
	public function addOnUrlFailed(callable $onUrlFailed): void
	{
		$this->onUrlFailed[] = $onUrlFailed;
	}


	/**
	 * @param non-negative-int|null $maxAllowedRedirects
	 * @throws SecurityTxtNoLocationHeaderException
	 * @throws SecurityTxtTooManyRedirectsException
	 * @throws SecurityTxtRedirectLoopException
	 * @throws SecurityTxtRedirectUrlTooLongException
	 * @throws SecurityTxtUrlUnsupportedSchemeException
	 * @throws SecurityTxtCannotParseHostnameException
	 */
	private function redirect(Url $url, Url $originalUrl, SecurityTxtFetcherResponse $response, ?int $maxAllowedRedirects): SecurityTxtFetcherUrl
	{
		if ($maxAllowedRedirects === null) {
			$maxAllowedRedirects = $this->maxAllowedRedirects;
		}
		$location = $response->getHeader('Location');
		if ($location === null) {
			throw new SecurityTxtNoLocationHeaderException($url, $response->getHttpCode(), $this->getRedirects($originalUrl));
		} else {
			$originalUrlString = new SecurityTxtPrintableValue($originalUrl)->render();
			// A URL this long is suspicious for a security.txt, and it is refused before the header is parsed, see the method, which measuring the parsed URL replaces
			if (SecurityTxtPhpUrlWorkarounds::getRedirectUrlMaxLength($location, $url) > self::MAX_REDIRECT_URL_LENGTH) {
				throw new SecurityTxtRedirectUrlTooLongException($url, $this->getRedirects($originalUrl), self::MAX_REDIRECT_URL_LENGTH);
			}
			try {
				$locationUrl = $this->urlParser->getRedirectUrl($location, $url);
			} catch (SecurityTxtCannotParseHostnameException $e) {
				// The parser is handed one header and knows nothing of the redirects that led to it
				throw new SecurityTxtCannotParseHostnameException($location, $this->getRedirects($originalUrl), $e);
			}
			$this->callOnCallback($this->onRedirect, $url, $locationUrl);
			$visited = $this->isVisited($locationUrl, $originalUrl, $this->redirects[$originalUrlString] ?? new SecurityTxtRedirects());
			// Where the redirect led rather than the header that said so: a `Location` can be relative, or spell a host in punycode, and this is a record of the URLs a check
			// went to and not of what a server typed
			$this->redirects[$originalUrlString] = ($this->redirects[$originalUrlString] ?? new SecurityTxtRedirects())->withRedirect($locationUrl);
			// Before the count, so the hop that closes a loop is called a loop even when it is also one too many
			if ($visited) {
				throw new SecurityTxtRedirectLoopException($url, $this->getRedirects($originalUrl));
			}
			if ($this->redirects[$originalUrlString]->count() > $maxAllowedRedirects) {
				throw new SecurityTxtTooManyRedirectsException($url, $this->getRedirects($originalUrl), $maxAllowedRedirects);
			}
			// Settled before the scheme is checked, so an unsupported one is reported with the URL spelled the way this library writes one, which is what a stored result is
			// read back with: `ftp://ex%41mple.com/` parses to `ftp://exAmple.com/`, which reading it back refuses
			try {
				$settledUrl = $this->urlParser->normalize($locationUrl);
			} catch (SecurityTxtCannotParseHostnameException $e) {
				throw new SecurityTxtCannotParseHostnameException($locationUrl->toAsciiString(), $this->getRedirects($originalUrl), $e);
			}
			return new SecurityTxtFetcherUrl($settledUrl, $this->getRedirects($originalUrl));
		}
	}


	/**
	 * Compared as it is going to be fetched, settled, because parsing leaves the case of a letter a percent escape uncovers in a host, and `equals()` ignores the fragment, which
	 * never reaches the server.
	 */
	private function isVisited(Url $locationUrl, Url $originalUrl, SecurityTxtRedirects $redirects): bool
	{
		$settledUrl = Url::parse($locationUrl->toAsciiString()) ?? $locationUrl;
		foreach ([$originalUrl, ...array_filter(array_map(Url::parse(...), $redirects->toStrings()))] as $visitedUrl) {
			if ($visitedUrl->equals($settledUrl)) {
				return true;
			}
		}
		return false;
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
