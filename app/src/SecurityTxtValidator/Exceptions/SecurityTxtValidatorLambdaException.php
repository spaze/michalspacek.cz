<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Exceptions;

use AsyncAws\Lambda\Result\InvocationResponse;
use Exception;
use Throwable;

final class SecurityTxtValidatorLambdaException extends Exception
{

	public function __construct(InvocationResponse $response, ?string $json, ?Throwable $previous = null)
	{
		$message = sprintf(
			"Lambda invocation error: status %s, version: %s, error: %s, log: %s, payload: %s",
			$response->getStatusCode() ?? '<no status>',
			$response->getExecutedVersion() ?? '<no version>',
			$response->getFunctionError() ?? '<no error>',
			$response->getLogResult() ?? '<no log>',
			$json ?? '<no json>',
		);
		parent::__construct($message, previous: $previous);
	}

}
