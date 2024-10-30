<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Robots;

use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class RobotsTest extends TestCase
{

	public function __construct(
		private readonly Response $httpResponse,
		private readonly Robots $robots,
	) {
	}


	public function testSetHeader(): void
	{
		$this->robots->setHeader([RobotsRule::NoIndex]);
		Assert::same('noindex', $this->httpResponse->getHeader('X-Robots-Tag'));

		$this->robots->setHeader([RobotsRule::NoIndex, RobotsRule::NoFollow]);
		Assert::same('noindex, nofollow', $this->httpResponse->getHeader('X-Robots-Tag'));
	}

}

TestCaseRunner::run(RobotsTest::class);
