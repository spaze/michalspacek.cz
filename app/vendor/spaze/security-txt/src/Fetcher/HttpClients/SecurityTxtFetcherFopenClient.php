<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\HttpClients;

use Override;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNoHttpCodeException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetcherResponse;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetcherUrl;

final readonly class SecurityTxtFetcherFopenClient implements SecurityTxtFetcherHttpClient
{

	private const int MAX_RESPONSE_LENGTH = 10_000;


	public function __construct(
		private string $userAgent,
	) {
	}


	/**
	 * @throws SecurityTxtCannotOpenUrlException
	 * @throws SecurityTxtNoHttpCodeException
	 */
	#[Override]
	public function getResponse(SecurityTxtFetcherUrl $url, ?string $contextHost): SecurityTxtFetcherResponse
	{

		$options = [
			'http' => [
				'follow_location' => false,
				'ignore_errors' => true,
				'user_agent' => $this->userAgent,
			],
		];
		if ($contextHost !== null) {
			$options['ssl'] = [
				'peer_name' => $contextHost,
			];
			$options['http']['header'] = ["Host: {$contextHost}"];
		}
		$fp = @fopen($url->getUrl(), 'r', context: stream_context_create($options)); // intentionally @, converted to exception
		if ($fp === false) {
			throw new SecurityTxtCannotOpenUrlException($url->getUrl(), $url->getRedirects());
		}
		$contents = stream_get_contents($fp, self::MAX_RESPONSE_LENGTH);
		if (strlen($contents) === self::MAX_RESPONSE_LENGTH) {
			if (stream_get_contents($fp, 1) !== '') {
				$truncated = true;
			}
		}
		$metadata = stream_get_meta_data($fp);
		fclose($fp);
		/** @var list<string> $wrapperData */
		$wrapperData = $metadata['wrapper_data'];
		if (preg_match('~^HTTP/[\d.]+ (\d+)~', $wrapperData[0], $matches) === 1) {
			$code = (int)$matches[1];
		} else {
			throw new SecurityTxtNoHttpCodeException($url->getUrl(), $url->getRedirects());
		}

		$headers = [];
		for ($i = 1; $i < count($wrapperData); $i++) {
			$parts = explode(':', $wrapperData[$i], 2);
			$headers[strtolower(trim($parts[0]))] = trim($parts[1]);
		}
		return new SecurityTxtFetcherResponse($code, $headers, $contents, $truncated ?? false);
	}

}
