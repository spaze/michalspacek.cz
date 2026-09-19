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
final class SecurityTxtValidatorFetchFunctionTimeoutTest extends TestCase
{

	/**
	 * A curl timeout comes back as a verdict the app stores against the host, a fetch function killed by AWS comes back
	 * as an error it cannot store, so the function's limit has to be above the longest curl can take: both locations
	 * are fetched, each may follow `fetchMaxRedirects` redirects, and every request has `fetchTimeout` seconds. The
	 * numbers live in different projects, and this is what keeps them in step.
	 */
	public function testTheFetchFunctionOutlivesTheFetcher(): void
	{
		$parameters = Neon::decodeFile(__DIR__ . '/../../config/parameters.neon');
		assert(is_array($parameters) && is_array($parameters['parameters']) && is_array($parameters['parameters']['securityTxtValidator']));
		$fetchTimeout = $parameters['parameters']['securityTxtValidator']['fetchTimeout'];
		$fetchMaxRedirects = $parameters['parameters']['securityTxtValidator']['fetchMaxRedirects'];
		assert(is_int($fetchTimeout) && is_int($fetchMaxRedirects));
		$serverless = Neon::decodeFile(__DIR__ . '/../../../lambda-security-txt/serverless.yml');
		assert(is_array($serverless) && is_array($serverless['functions']) && is_array($serverless['functions']['fetch']));
		$lambdaTimeout = $serverless['functions']['fetch']['timeout'];
		assert(is_int($lambdaTimeout));
		$fetcherSeconds = 2 * (1 + $fetchMaxRedirects) * $fetchTimeout;
		Assert::true($lambdaTimeout > $fetcherSeconds, "The fetch function on Lambda may run for {$lambdaTimeout} seconds but the fetcher's requests may take {$fetcherSeconds}");
	}

}

TestCaseRunner::run(SecurityTxtValidatorFetchFunctionTimeoutTest::class);
