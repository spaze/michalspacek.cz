<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SecurityHeaders\IntegrityPolicy;

use MichalSpacekCz\Http\StructuredHeaders;
use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class IntegrityPolicyDefaultTest extends TestCase
{

	public function __construct(
		private readonly Response $httpResponse,
		private readonly StructuredHeaders $structuredHeaders,
	) {
	}


	public function testSet(): void
	{
		new IntegrityPolicyDefault($this->httpResponse, $this->structuredHeaders)->set();
		Assert::same('blocked-destinations=(script), endpoints=(default)', $this->httpResponse->getHeader(IntegrityPolicy::HEADER_NAME));
	}

}

TestCaseRunner::run(IntegrityPolicyDefaultTest::class);
