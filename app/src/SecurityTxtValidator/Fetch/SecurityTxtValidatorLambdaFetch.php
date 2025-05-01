<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Fetch;

use AsyncAws\Lambda\LambdaClient;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorException;
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
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;
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
	) {
	}


	/**
	 * @throws SecurityTxtFetcherException
	 * @throws SecurityTxtValidatorException
	 */
	#[Override]
	public function fetch(SecurityTxtValidatorUrl $url, bool $requireTopLevelLocation): SecurityTxtFetchResult
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
	 * @throws SecurityTxtFetcherException
	 * @throws SecurityTxtValidatorException
	 * @throws SecurityTxtValidatorLambdaException
	 */
	private function fetchAndDecode(SecurityTxtValidatorUrl $url, bool $requireTopLevelLocation): SecurityTxtFetchResult
	{
		$lambdaResult = $this->lambdaClient->invoke([
			'FunctionName' => $this->lambdaFunctions->getFetch(),
			'Payload' => Json::encode([
				'host' => $url->getUrl()->toUnicodeString(),
				'requireTopLevelLocation' => $requireTopLevelLocation,
				'noIpv6' => $this->noIpv6,
				'userAgent' => $this->userAgent,
			]),
		]);
		$json = $lambdaResult->getPayload();

		$decoded = $this->lambdaResponse->decode($lambdaResult, $json);
		$this->lambdaVersionCheck->checkResponse($decoded, 3, false);
		if (isset($decoded['status']) && $decoded['status'] === 'Error') {
			throw $this->securityTxtJson->createFetcherExceptionFromJsonValues($decoded);
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
		if (!is_array($decoded['fetchResult'])) {
			throw new SecurityTxtValidatorException("fetchResult is not an array: {$json}");
		}
		return $this->securityTxtJson->createFetchResultFromJsonValues($decoded['fetchResult']);
	}

}
