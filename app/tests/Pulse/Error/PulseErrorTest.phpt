<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace Pulse\Error;

use MichalSpacekCz\Pulse\Error\PulseError;
use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Responses\TextResponse;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PulseErrorTest extends TestCase
{

	public function __construct(
		private readonly PulseError $pulseError,
		private readonly Response $httpResponse,
	) {
	}


	public function testAction(): void
	{
		$sentResponse = null;
		$this->pulseError->action(function (TextResponse $response) use (&$sentResponse): void {
			$sentResponse = $response->getSource();
		});
		assert(is_string($sentResponse));
		Assert::same('noindex, nofollow', $this->httpResponse->getHeader('X-Robots-Tag'));
		Assert::contains('<title>Page not found!</title>', $sentResponse);
		Assert::contains('check your speling', $sentResponse);
	}

}

TestCaseRunner::run(PulseErrorTest::class);
