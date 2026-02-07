<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SecurityHeaders\IntegrityPolicy;

use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class IntegrityPolicyNoneTest extends TestCase
{

	public function __construct(
		private readonly Response $httpResponse,
	) {
	}


	public function testSet(): void
	{
		$this->httpResponse->setHeader(IntegrityPolicy::HEADER_NAME, 'foo');
		new IntegrityPolicyNone($this->httpResponse)->set();
		Assert::null($this->httpResponse->getHeader(IntegrityPolicy::HEADER_NAME));
	}

}

TestCaseRunner::run(IntegrityPolicyNoneTest::class);
