<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\HttpClients;

use CurlHandle;
use LogicException;
use Override;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlContentEncodingException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlExtensionNotLoadedException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlUserAgentInvalidException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtConnectedToWrongIpAddressException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNoHttpCodeException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetcherResponse;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetcherUrl;
use Spaze\SecurityTxt\Fetcher\SecurityTxtIpAddressType;
use Spaze\SecurityTxt\SecurityTxtHost;

final readonly class SecurityTxtFetcherCurlClient implements SecurityTxtFetcherHttpClient
{
	private const string DEFAULT_USER_AGENT = 'Mozilla/5.0 (compatible; spaze/security-txt; +https://github.com/spaze/security-txt)';
	private const int DEFAULT_MAX_RESPONSE_LENGTH = 10_000;
	private const int DEFAULT_TIMEOUT = 10;
	private const int DEFAULT_CONNECT_TIMEOUT = 5;
	private const string STATUS_LINE = '~^HTTP/\S+ ([0-9]{3})~';

	private string $userAgent;
	private int $maxResponseLength;
	private int $timeout;
	private int $connectTimeout;


	public function __construct(
		?string $userAgent = null,
		?int $maxResponseLength = null,
		?int $timeout = null,
		?int $connectTimeout = null,
	) {
		$this->userAgent = $userAgent ?? self::DEFAULT_USER_AGENT;
		$this->maxResponseLength = $maxResponseLength ?? self::DEFAULT_MAX_RESPONSE_LENGTH;
		$this->timeout = $timeout ?? self::DEFAULT_TIMEOUT;
		$this->connectTimeout = $connectTimeout ?? self::DEFAULT_CONNECT_TIMEOUT;
		if (strlen($this->userAgent) === 0) {
			throw new LogicException('userAgent must not be an empty string');
		}
		if ($this->maxResponseLength <= 0) {
			throw new LogicException('maxResponseLength must be greater than 0');
		}
		if ($this->timeout <= 0) {
			throw new LogicException('timeout must be greater than 0');
		}
		if ($this->connectTimeout <= 0) {
			throw new LogicException('connectTimeout must be greater than 0');
		}
		if ($this->connectTimeout > $this->timeout) {
			throw new LogicException('connectTimeout must not be greater than timeout (timeout is for the whole transfer, connecting included)');
		}
	}


	/**
	 * @throws SecurityTxtCannotOpenUrlException
	 * @throws SecurityTxtCannotOpenUrlContentEncodingException
	 * @throws SecurityTxtCannotOpenUrlExtensionNotLoadedException
	 * @throws SecurityTxtNoHttpCodeException
	 * @throws SecurityTxtConnectedToWrongIpAddressException
	 * @throws SecurityTxtCannotOpenUrlUserAgentInvalidException
	 */
	#[Override]
	public function getResponse(SecurityTxtFetcherUrl $url, SecurityTxtHost $host, string $ipAddress, SecurityTxtIpAddressType $ipAddressType): SecurityTxtFetcherResponse
	{
		if (!extension_loaded('curl')) {
			throw new SecurityTxtCannotOpenUrlExtensionNotLoadedException($url->getUrl());
		}
		if (preg_match('/[\x00-\x1F\x7F]/', $this->userAgent) === 1) {
			throw new SecurityTxtCannotOpenUrlUserAgentInvalidException($url->getUrl());
		}
		// The ASCII serialization, so the host curl parses out of it is the one `CURLOPT_RESOLVE` below is keyed by and the one that goes into SNI. A curl built with libidn would
		// convert a readable host itself and arrive at the same place, but not every curl is, and this does not depend on which one is
		$ch = curl_init($url->getUrl()->toAsciiString());
		if ($ch === false) {
			throw new SecurityTxtCannotOpenUrlException($url->getUrl(), $url->getRedirects());
		}

		$rawHeaders = [];
		$contents = '';
		$truncated = false;
		$port = $url->getUrl()->getPort();
		$defaultPort = $url->getUrl()->getScheme() === 'http' ? 80 : 443;
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => false,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_FAILONERROR => false,
			CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
			CURLOPT_TIMEOUT => $this->timeout,
			CURLOPT_LOW_SPEED_LIMIT => 10,
			CURLOPT_LOW_SPEED_TIME => $this->getLowSpeedTime(),
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_ENCODING => '', // '' means that the Accept-Encoding: header containing all supported encoding types is sent
			CURLOPT_FORBID_REUSE => true,
			CURLOPT_FRESH_CONNECT => true,
			CURLOPT_HTTPHEADER => ["Host: {$host->getAscii()}" . ($port !== null ? ":{$port}" : '')],
			CURLOPT_USERAGENT => $this->userAgent,
			CURLOPT_HEADER => false,
			CURLOPT_RESOLVE => [sprintf('%s:%s:%s', $host->getAscii(), $port ?? $defaultPort, $ipAddressType === SecurityTxtIpAddressType::V6 ? "[{$ipAddress}]" : $ipAddress)],
			CURLOPT_HEADERFUNCTION => function (CurlHandle $ch, string $header) use (&$rawHeaders): int {
				$rawHeaders[] = trim($header);
				return strlen($header);
			},
			CURLOPT_WRITEFUNCTION => function (CurlHandle $ch, string $data) use (&$contents, &$truncated): int {
				$length = strlen($data);
				$remaining = $this->maxResponseLength - strlen($contents);
				// Returning 0 stops transfer, but also throws CURLE_WRITE_ERROR, which we'll have to discard
				if ($remaining <= 0) {
					$truncated = true;
					return 0;
				}
				if ($length > $remaining) {
					$contents .= substr($data, 0, $remaining);
					$truncated = true;
					return 0;
				}
				$contents .= $data;
				return $length;
			},
		]);

		$result = curl_exec($ch);
		if ($result === false) {
			$error = curl_errno($ch);
			// What the server claimed to have encoded the body with, `utf8` say, which is a charset and not a content coding, is the thing to fix, more than that curl failed
			$contentEncoding = $error === CURLE_BAD_CONTENT_ENCODING ? $this->getContentEncoding($rawHeaders) : null;
			if ($contentEncoding !== null) {
				throw new SecurityTxtCannotOpenUrlContentEncodingException($url->getUrl(), $url->getRedirects(), $ipAddress, $ipAddressType, $contentEncoding);
			}
			if ($error !== CURLE_WRITE_ERROR || !$truncated) {
				// Deliberately not curl_error(), that one embeds server controlled strings, see the exception's $error docs
				throw new SecurityTxtCannotOpenUrlException(
					$url->getUrl(),
					$url->getRedirects(),
					$ipAddress,
					$ipAddressType,
					curl_strerror($error),
				);
			}
		}

		$primaryIp = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
		$primaryIpBinary = inet_pton($primaryIp);
		$expectedIpBinary = inet_pton($ipAddress);
		if ($primaryIpBinary === false || $expectedIpBinary === false || $primaryIpBinary !== $expectedIpBinary) {
			throw new SecurityTxtConnectedToWrongIpAddressException($ipAddress, $primaryIp, $url->getUrl(), $url->getRedirects());
		}

		$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		if ($code === 0) {
			throw new SecurityTxtNoHttpCodeException($url->getUrl(), $url->getRedirects());
		}

		return new SecurityTxtFetcherResponse(
			$code,
			$this->getHeaders($rawHeaders),
			$contents,
			$truncated,
			$ipAddress,
			$ipAddressType,
		);
	}


	/**
	 * The `Content-Encoding` of the final response, its lines joined the way RFC 9110 combines a field, but only once all of that response's header lines have arrived:
	 * curl reports a `Transfer-Encoding` it rejects while still reading them with the same error, and then it is not the content coding that failed.
	 *
	 * @param list<string> $rawHeaders
	 */
	private function getContentEncoding(array $rawHeaders): ?string
	{
		if ($rawHeaders === [] || $rawHeaders[array_key_last($rawHeaders)] !== '') {
			return null;
		}
		$codings = [];
		$final = false;
		foreach ($rawHeaders as $line) {
			// Each status line starts the header block of another response, and only the one after an informational 1xx response is the final one
			if (preg_match(self::STATUS_LINE, $line, $matches) === 1) {
				$codings = [];
				$final = (int)$matches[1] >= 200;
				continue;
			}
			$parts = explode(':', $line, 2);
			if (count($parts) === 2 && strtolower(trim($parts[0])) === 'content-encoding' && trim($parts[1]) !== '') {
				$codings[] = trim($parts[1]);
			}
		}
		return $final && $codings !== [] ? implode(', ', $codings) : null;
	}


	/**
	 * @param list<string> $rawHeaders
	 * @return array<lowercase-string, string>
	 */
	private function getHeaders(array $rawHeaders): array
	{
		$headers = [];
		foreach ($rawHeaders as $line) {
			// Each status line starts another response's header lines, so only the final response's are kept, not those of a 1xx response before it
			if (preg_match(self::STATUS_LINE, $line) === 1) {
				$headers = [];
				continue;
			}
			if ($line === '') {
				continue;
			}
			$parts = explode(':', $line, 2);
			if (count($parts) === 2) {
				$headers[strtolower(trim($parts[0]))] = trim($parts[1]);
			}
		}
		return $headers;
	}


	/**
	 * Scaled down from the timeout, so a transfer that has gone quiet is given up early rather than only when the whole timeout runs out. Kept above zero because curl reads
	 * a zero window as stall detection switched off, and capped so raising the timeout for a slow host does not also lengthen the wait on a stalled one
	 */
	private function getLowSpeedTime(): int
	{
		return min(5, max(1, intdiv($this->timeout, 2)));
	}

}
