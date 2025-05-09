<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

use LogicException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotReadUrlException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressInvalidTypeException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNoHttpCodeException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNoLocationHeaderException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtOnlyIpv6HostButIpv6DisabledException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtTooManyRedirectsException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtUrlNotFoundException;
use Spaze\SecurityTxt\Fetcher\HttpClients\SecurityTxtFetcherHttpClient;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\Violations\SecurityTxtContentTypeInvalid;
use Spaze\SecurityTxt\Violations\SecurityTxtContentTypeWrongCharset;
use Spaze\SecurityTxt\Violations\SecurityTxtSchemeNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtTopLevelDiffers;
use Spaze\SecurityTxt\Violations\SecurityTxtTopLevelPathOnly;
use Spaze\SecurityTxt\Violations\SecurityTxtWellKnownPathOnly;

final class SecurityTxtFetcher
{

	private const int MAX_ALLOWED_REDIRECTS = 5;

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


	public function __construct(
		private readonly SecurityTxtFetcherHttpClient $httpClient,
	) {
	}


	/**
	 * @throws SecurityTxtCannotOpenUrlException
	 * @throws SecurityTxtCannotReadUrlException
	 * @throws SecurityTxtNotFoundException
	 * @throws SecurityTxtTooManyRedirectsException
	 * @throws SecurityTxtHostNotFoundException
	 * @throws SecurityTxtNoHttpCodeException
	 * @throws SecurityTxtNoLocationHeaderException
	 * @throws SecurityTxtOnlyIpv6HostButIpv6DisabledException
	 * @throws SecurityTxtHostIpAddressInvalidTypeException
	 * @throws SecurityTxtHostIpAddressNotFoundException
	 */
	public function fetchHost(string $host, bool $noIpv6 = false): SecurityTxtFetchResult
	{
		$wellKnown = $this->fetchUrl('https://%s/.well-known/security.txt', $host, $noIpv6);
		$topLevel = $this->fetchUrl('https://%s/security.txt', $host, $noIpv6);
		return $this->getResult($wellKnown, $topLevel);
	}


	/**
	 * @throws SecurityTxtTooManyRedirectsException
	 * @throws SecurityTxtHostNotFoundException
	 * @throws SecurityTxtCannotReadUrlException
	 * @throws SecurityTxtCannotOpenUrlException
	 * @throws SecurityTxtNotFoundException
	 * @throws SecurityTxtNoHttpCodeException
	 * @throws SecurityTxtNoLocationHeaderException
	 * @throws SecurityTxtOnlyIpv6HostButIpv6DisabledException
	 * @throws SecurityTxtHostIpAddressInvalidTypeException
	 * @throws SecurityTxtHostIpAddressNotFoundException
	 */
	private function fetchUrl(string $urlTemplate, string $host, bool $noIpv6): SecurityTxtFetcherFetchHostResult
	{
		$url = $this->buildUrl($urlTemplate, $host);
		$finalUrl = $url;
		$this->callOnCallback($this->onUrl, $url);
		try {
			$this->redirects[$url] = [];
			$records = @dns_get_record($host, DNS_A | DNS_AAAA); // intentionally @, converted to exception
			if ($records === false) {
				throw new SecurityTxtHostNotFoundException($url, $host);
			}
			$records = array_merge(...$records);
			$ipRecord = $records['ip'] ?? null;
			$ipv6Record = $records['ipv6'] ?? null;
			if ($ipRecord !== null && !is_string($ipRecord)) {
				throw new SecurityTxtHostIpAddressInvalidTypeException($host, get_debug_type($ipRecord), $url);
			}
			if ($ipv6Record !== null && !is_string($ipv6Record)) {
				throw new SecurityTxtHostIpAddressInvalidTypeException($host, get_debug_type($ipv6Record), $url);
			}
			if ($noIpv6 && $ipv6Record !== null && $ipRecord === null) {
				throw new SecurityTxtOnlyIpv6HostButIpv6DisabledException($host, $ipv6Record, $url);
			}
			if (!$noIpv6 && $ipv6Record !== null) {
				$ipAddress = "[{$ipv6Record}]";
			} elseif ($ipRecord !== null) {
				$ipAddress = $ipRecord;
			}
			if (!isset($ipAddress)) {
				throw new SecurityTxtHostIpAddressNotFoundException($url, $host);
			}
			$response = $this->getResponse($this->buildUrl($urlTemplate, $ipAddress), $urlTemplate, $host, true, $finalUrl);
		} catch (SecurityTxtUrlNotFoundException $e) {
			$this->callOnCallback($this->onUrlNotFound, $e->getUrl());
			$response = null;
		}
		return new SecurityTxtFetcherFetchHostResult(
			$url,
			$finalUrl,
			$response,
			$e ?? null,
		);
	}


	/**
	 * @throws SecurityTxtTooManyRedirectsException
	 * @throws SecurityTxtCannotReadUrlException
	 * @throws SecurityTxtNotFoundException
	 * @throws SecurityTxtCannotOpenUrlException
	 * @throws SecurityTxtUrlNotFoundException
	 * @throws SecurityTxtNoHttpCodeException
	 * @throws SecurityTxtNoLocationHeaderException
	 */
	private function getResponse(string $url, string $urlTemplate, string $host, bool $useHostForContextHost, string &$finalUrl): SecurityTxtFetcherResponse
	{
		$builtUrl = $this->buildUrl($urlTemplate, $host);
		$redirects = $this->redirects[$builtUrl] ?? [];
		if ($redirects !== []) {
			array_unshift($redirects, $builtUrl);
		}
		$response = $this->httpClient->getResponse(new SecurityTxtFetcherUrl($url, $redirects), $useHostForContextHost ? $host : null);
		if ($response->getHttpCode() >= 400) {
			throw new SecurityTxtUrlNotFoundException($url, $response->getHttpCode());
		}
		if ($response->getHttpCode() >= 300) {
			return $this->redirect($url, $response, $urlTemplate, $host, $finalUrl);
		}
		return $response;
	}


	/**
	 * @throws SecurityTxtNotFoundException
	 */
	private function getResult(SecurityTxtFetcherFetchHostResult $wellKnown, SecurityTxtFetcherFetchHostResult $topLevel): SecurityTxtFetchResult
	{
		$errors = $warnings = [];
		$wellKnownContents = $wellKnown->getContents();
		$topLevelContents = $topLevel->getContents();
		if ($wellKnownContents === null && $topLevelContents === null) {
			throw new SecurityTxtNotFoundException([
				$wellKnown->getUrl() => $wellKnown->getHttpCode(),
				$topLevel->getUrl() => $topLevel->getHttpCode(),
			]);
		} elseif ($wellKnownContents !== null && $topLevelContents === null) {
			$warnings[] = new SecurityTxtWellKnownPathOnly();
			$result = $wellKnown;
			$contents = $wellKnownContents;
		} elseif ($wellKnownContents === null) {
			$warnings[] = new SecurityTxtTopLevelPathOnly();
			$result = $topLevel;
			$contents = $topLevelContents;
		} elseif ($wellKnownContents !== $topLevelContents) {
			if ($topLevelContents === null) {
				throw new LogicException('This should not happen');
			}
			$warnings[] = new SecurityTxtTopLevelDiffers($wellKnownContents, $topLevelContents);
			$result = $wellKnown;
			$contents = $wellKnownContents;
		} else {
			$result = $wellKnown;
			$contents = $wellKnownContents;
		}
		$this->callOnCallback($this->onFinalUrl, $result->getFinalUrl());

		$contentTypeHeader = $result->getContentTypeHeader();
		$headerParts = $contentTypeHeader !== null ? explode(';', $contentTypeHeader, 2) : [];
		$contentType = isset($headerParts[0]) ? trim($headerParts[0]) : null;
		$charset = isset($headerParts[1]) ? trim($headerParts[1]) : null;
		if ($contentType === null || strtolower($contentType) !== SecurityTxt::CONTENT_TYPE) {
			$errors[] = new SecurityTxtContentTypeInvalid($result->getUrl(), $contentType);
		} elseif ($charset === null || strtolower($charset) !== SecurityTxt::CHARSET) {
			$errors[] = new SecurityTxtContentTypeWrongCharset($result->getUrl(), $contentType, $charset);
		}
		$scheme = parse_url($result->getUrl(), PHP_URL_SCHEME);
		if ($scheme !== 'https') {
			$errors[] = new SecurityTxtSchemeNotHttps($result->getUrl());
		}
		return new SecurityTxtFetchResult(
			$result->getUrl(),
			$result->getFinalUrl(),
			$this->redirects,
			$contents,
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


	private function buildUrl(string $urlTemplate, string $host): string
	{
		return sprintf($urlTemplate, $host);
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
	 * @throws SecurityTxtCannotOpenUrlException
	 * @throws SecurityTxtCannotReadUrlException
	 * @throws SecurityTxtNoHttpCodeException
	 * @throws SecurityTxtNoLocationHeaderException
	 * @throws SecurityTxtNotFoundException
	 * @throws SecurityTxtTooManyRedirectsException
	 * @throws SecurityTxtUrlNotFoundException
	 */
	private function redirect(string $url, SecurityTxtFetcherResponse $response, string $urlTemplate, string $host, string &$finalUrl): SecurityTxtFetcherResponse
	{
		$location = $response->getHeader('Location');
		if ($location === null) {
			throw new SecurityTxtNoLocationHeaderException($url, $response->getHttpCode());
		} else {
			$originalUrl = $this->buildUrl($urlTemplate, $host);
			$previousUrl = $this->redirects[$originalUrl] !== [] ? $this->redirects[$originalUrl][array_key_last($this->redirects[$originalUrl])] : $originalUrl;
			$this->callOnCallback($this->onRedirect, $previousUrl, $location);
			$this->redirects[$originalUrl][] = $location;
			$finalUrl = $location;
			if (count($this->redirects[$originalUrl]) > self::MAX_ALLOWED_REDIRECTS) {
				throw new SecurityTxtTooManyRedirectsException($url, $this->redirects[$originalUrl], self::MAX_ALLOWED_REDIRECTS);
			}
			return $this->getResponse($location, $urlTemplate, $host, false, $finalUrl);
		}
	}

}
