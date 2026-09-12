<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\HttpClients;

use CurlHandle;
use LogicException;
use Override;
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

	public function __construct(
		private string $userAgent = 'Mozilla/5.0 (compatible; spaze/security-txt; +https://github.com/spaze/security-txt)',
		private int $maxResponseLength = 10_000,
	) {
		if (strlen($this->userAgent) === 0) {
			throw new LogicException('userAgent must not be an empty string');
		}
		if ($this->maxResponseLength <= 0) {
			throw new LogicException('maxResponseLength must be greater than 0');
		}
	}


	/**
	 * @throws SecurityTxtCannotOpenUrlException
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
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_LOW_SPEED_LIMIT => 10,
			CURLOPT_LOW_SPEED_TIME => 5,
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

		$headers = [];
		foreach ($rawHeaders as $i => $line) {
			if ($i === 0) {
				// status line, already handled via curl_getinfo
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

		return new SecurityTxtFetcherResponse(
			$code,
			$headers,
			$contents,
			$truncated,
			$ipAddress,
			$ipAddressType,
		);
	}

}
