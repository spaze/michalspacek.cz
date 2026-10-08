<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use AsyncAws\Lambda\Result\InvocationResponse;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorException;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorLambdaException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

final readonly class LambdaResponse
{

	/**
	 * @return array<array-key, mixed>
	 * @throws JsonException
	 * @throws SecurityTxtValidatorException
	 * @throws SecurityTxtValidatorLambdaException
	 */
	public function decode(InvocationResponse $lambdaResult, ?string $json): array
	{
		$lambdaFunctionError = $lambdaResult->getFunctionError();
		if ($json === null || $lambdaFunctionError !== null) {
			throw new SecurityTxtValidatorLambdaException($lambdaResult, $json);
		}
		$decoded = Json::decode($json, true);
		if (!is_array($decoded)) {
			throw new SecurityTxtValidatorException("JSON is not an array: {$json}");
		}
		return $decoded;
	}

}
