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

final readonly class SecurityTxtFetcherCurlClient implements SecurityTxtFetcherHttpClient
{

	public function __construct(
		private string $userAgent = 'Mozilla/5.0 (compatible; spaze/security-txt; +https://github.com/spaze/security-txt)',
		private int $maxResponseLength = 10_000,
	) {
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
	public function getResponse(SecurityTxtFetcherUrl $url, string $host, string $ipAddress, SecurityTxtIpAddressType $ipAddressType): SecurityTxtFetcherResponse
	{
		if (!extension_loaded('curl')) {
			throw new SecurityTxtCannotOpenUrlExtensionNotLoadedException($url->getUrl()->toUnicodeString());
		}
		if (preg_match('/[\x00-\x1F\x7F]/', $this->userAgent) === 1) {
			throw new SecurityTxtCannotOpenUrlUserAgentInvalidException($url->getUrl()->toUnicodeString());
		}
		$ch = curl_init($url->getUrl()->toUnicodeString());
		if ($ch === false) {
			throw new SecurityTxtCannotOpenUrlException($url->getUrl()->toUnicodeString(), $url->getRedirects());
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
			CURLOPT_HTTPHEADER => ["Host: {$host}" . ($port !== null ? ":{$port}" : '')],
			CURLOPT_USERAGENT => $this->userAgent,
			CURLOPT_HEADER => false,
			CURLOPT_RESOLVE => [sprintf('%s:%s:%s', $host, $port ?? $defaultPort, $ipAddressType === SecurityTxtIpAddressType::V6 ? "[{$ipAddress}]" : $ipAddress)],
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
				throw new SecurityTxtCannotOpenUrlException($url->getUrl()->toUnicodeString(), $url->getRedirects());
			}
		}

		$primaryIp = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
		$primaryIpBinary = inet_pton($primaryIp);
		$expectedIpBinary = inet_pton($ipAddress);
		if ($primaryIpBinary === false || $expectedIpBinary === false || $primaryIpBinary !== $expectedIpBinary) {
			throw new SecurityTxtConnectedToWrongIpAddressException($ipAddress, $primaryIp, $url->getUrl()->toUnicodeString(), $url->getRedirects());
		}

		$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		if ($code === 0) {
			throw new SecurityTxtNoHttpCodeException($url->getUrl()->toUnicodeString(), $url->getRedirects());
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
