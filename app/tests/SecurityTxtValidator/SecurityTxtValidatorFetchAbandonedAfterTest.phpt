<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use DateTimeImmutable;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Neon\Neon;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class SecurityTxtValidatorFetchAbandonedAfterTest extends TestCase
{

	/**
	 * A claim older than `fetchAbandonedAfter` is taken as left behind by a fetch that died, so the parameter has to
	 * outlast the longest a fetch can run, which is how long AWS lets the fetch function run before killing it. The two
	 * numbers live in different projects, and this is what keeps them in step.
	 */
	public function testAClaimOutlivesTheFetchFunction(): void
	{
		$parameters = Neon::decodeFile(__DIR__ . '/../../config/parameters.neon');
		assert(is_array($parameters) && is_array($parameters['parameters']) && is_array($parameters['parameters']['securityTxtValidator']));
		$fetchAbandonedAfter = $parameters['parameters']['securityTxtValidator']['fetchAbandonedAfter'];
		assert(is_string($fetchAbandonedAfter));
		$serverless = Neon::decodeFile(__DIR__ . '/../../../lambda-security-txt/serverless.yml');
		assert(is_array($serverless) && is_array($serverless['functions']) && is_array($serverless['functions']['fetch']));
		$lambdaTimeout = $serverless['functions']['fetch']['timeout'];
		assert(is_int($lambdaTimeout));
		$abandonedAfterSeconds = new DateTimeImmutable('@0')->modify("+{$fetchAbandonedAfter}")->getTimestamp();
		Assert::true($abandonedAfterSeconds > $lambdaTimeout, "fetchAbandonedAfter is {$abandonedAfterSeconds} seconds but the fetch function on Lambda may run for {$lambdaTimeout}");
	}

}

TestCaseRunner::run(SecurityTxtValidatorFetchAbandonedAfterTest::class);
