<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Fetch;

use AsyncAws\Lambda\LambdaClient;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorException;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorFetchFailedException;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorLambdaException;
use MichalSpacekCz\SecurityTxtValidator\LambdaFunctions;
use MichalSpacekCz\SecurityTxtValidator\LambdaResponse;
use MichalSpacekCz\SecurityTxtValidator\LambdaVersionCheck\SecurityTxtValidatorLambdaVersionCheck;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtValidatorLogger;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtValidatorUrl;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Override;
use Spaze\SecurityTxt\Check\Exceptions\SecurityTxtCannotParseJsonException;
use Spaze\SecurityTxt\Json\SecurityTxtJson;

readonly final class SecurityTxtValidatorLambdaFetch implements SecurityTxtValidatorFetch
{

	public function __construct(
		private LambdaClient $lambdaClient,
		private LambdaResponse $lambdaResponse,
		private LambdaFunctions $lambdaFunctions,
		private SecurityTxtJson $securityTxtJson,
		private SecurityTxtValidatorLambdaVersionCheck $lambdaVersionCheck,
		private SecurityTxtValidatorLogger $logger,
		private bool $noIpv6,
		private string $userAgent,
		private int $timeout,
		private int $connectTimeout,
		private int $maxAllowedRedirects,
	) {
	}


	/**
	 * @throws SecurityTxtValidatorFetchFailedException
	 * @throws SecurityTxtValidatorException
	 */
	#[Override]
	public function fetch(SecurityTxtValidatorUrl $url, bool $requireTopLevelLocation): SecurityTxtValidatorFetchResponse
	{
		try {
			return $this->fetchAndDecode($url, $requireTopLevelLocation);
		} catch (SecurityTxtValidatorLambdaException $e) {
			throw new SecurityTxtValidatorException('Lambda response decoding failure', previous: $e);
		} catch (SecurityTxtCannotParseJsonException $e) {
			throw new SecurityTxtValidatorException('Lambda exception parsing failure', previous: $e);
		} catch (JsonException $e) {
			throw new SecurityTxtValidatorException('Lambda JSON failure', previous: $e);
		}
	}


	/**
	 * @throws JsonException
	 * @throws SecurityTxtCannotParseJsonException
	 * @throws SecurityTxtValidatorFetchFailedException
	 * @throws SecurityTxtValidatorException
	 * @throws SecurityTxtValidatorLambdaException
	 */
	private function fetchAndDecode(SecurityTxtValidatorUrl $url, bool $requireTopLevelLocation): SecurityTxtValidatorFetchResponse
	{
		$lambdaResult = $this->lambdaClient->invoke([
			'FunctionName' => $this->lambdaFunctions->getFetch(),
			'Payload' => Json::encode([
				'host' => $url->getBaseUrl()->toAsciiString(),
				'requireTopLevelLocation' => $requireTopLevelLocation,
				'noIpv6' => $this->noIpv6,
				'userAgent' => $this->userAgent,
				'timeout' => $this->timeout,
				'connectTimeout' => $this->connectTimeout,
				'maxAllowedRedirects' => $this->maxAllowedRedirects,
			]),
		]);
		$json = $lambdaResult->getPayload();

		$decoded = $this->lambdaResponse->decode($lambdaResult, $json);
		$fetcherVersion = $this->lambdaVersionCheck->getVersionFromResponse($decoded);
		$this->lambdaVersionCheck->checkResponse($fetcherVersion, 3, false);
		if (isset($decoded['status']) && $decoded['status'] === 'Error') {
			throw new SecurityTxtValidatorFetchFailedException($this->securityTxtJson->createFetcherExceptionFromJsonValues($decoded), $fetcherVersion);
		} elseif (isset($decoded['errorType']) || (isset($decoded['status']) && $decoded['status'] !== 'OK')) {
			$statusCode = $lambdaResult->getStatusCode() ?? '<missing>';
			$functionError = $lambdaResult->getFunctionError() ?? '<missing>';
			$this->logger->log($url->getHost(), sprintf(
				'Lambda invocation error: status %s, version: %s, error: %s, log: %s, payload: %s',
				$statusCode,
				$lambdaResult->getExecutedVersion() ?? '<missing>',
				$functionError,
				$lambdaResult->getLogResult() ?? '<missing>',
				$json ?? '<missing>',
			));
			throw new SecurityTxtValidatorException("Lambda invocation error: status {$statusCode}, error: {$functionError}");
		}
		if (!isset($decoded['fetchResult']) || !is_array($decoded['fetchResult'])) {
			throw new SecurityTxtValidatorException("fetchResult is missing or not an array: {$json}");
		}
		return new SecurityTxtValidatorFetchResponse($this->securityTxtJson->createFetchResultFromJsonValues($decoded['fetchResult']), $fetcherVersion);
	}

}
