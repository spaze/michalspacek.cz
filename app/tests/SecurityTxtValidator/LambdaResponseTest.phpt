<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use AsyncAws\Core\Response;
use AsyncAws\Core\Test\Http\SimpleMockedResponse;
use AsyncAws\Lambda\Result\InvocationResponse;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorLambdaException;
use MichalSpacekCz\Test\TestCaseRunner;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class LambdaResponseTest extends TestCase
{

	public function __construct(
		private readonly LambdaResponse $lambdaResponse,
	) {
	}


	public function testDecodeThrowsLambdaException(): void
	{
		$httpResponse = new SimpleMockedResponse(
			'',
			[
				'x-amz-executed-version' => ['13.37'],
				'x-amz-function-error' => ['Le error'],
				'x-amz-log-result' => ['Logged message'],
			],
			202,
		);
		$httpClient = new MockHttpClient($httpResponse);
		$logger = new NullLogger();
		$response = new Response($httpResponse, $httpClient, $logger);
		$result = new InvocationResponse($response);
		Assert::throws(function () use ($result) {
			$this->lambdaResponse->decode($result, null);
		}, SecurityTxtValidatorLambdaException::class, 'Lambda invocation error: status 202, version: 13.37, error: Le error, log: Logged message, payload: <no json>');
	}

}

TestCaseRunner::run(LambdaResponseTest::class);
