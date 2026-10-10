<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Neon\Neon;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class SecurityTxtValidatorLambdaRegionTest extends TestCase
{

	/**
	 * The app invokes the fetch function in the region the parameter names, and the function lives where
	 * `serverless.yml` deploys it. An empty or wrong region does not fail, the SDK quietly asks another region,
	 * where the function does not exist and the key is not allowed, so the two files are kept in step here.
	 */
	public function testTheAppInvokesTheFunctionWhereItIsDeployed(): void
	{
		$parameters = Neon::decodeFile(__DIR__ . '/../../config/parameters.neon');
		assert(is_array($parameters) && is_array($parameters['parameters']) && is_array($parameters['parameters']['awsLambda']) && is_array($parameters['parameters']['awsLambda']['securityTxtValidator']));
		$appRegion = $parameters['parameters']['awsLambda']['securityTxtValidator']['region'];
		$serverless = Neon::decodeFile(__DIR__ . '/../../../lambda-security-txt/serverless.yml');
		assert(is_array($serverless) && is_array($serverless['provider']));
		$functionRegion = $serverless['provider']['region'];
		Assert::type('string', $appRegion);
		Assert::same($functionRegion, $appRegion);
	}

}

TestCaseRunner::run(SecurityTxtValidatorLambdaRegionTest::class);
