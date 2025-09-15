<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Error;

use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Application\Responses\ForwardResponse;
use Nette\Application\Responses\TextResponse;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class AppErrorTest extends TestCase
{

	public function __construct(
		private readonly AppError $appError,
		private readonly NullLogger $logger,
	) {
	}


	public function testTextResponse(): void
	{
		$request = new Request('name', params: ['exception' => new RuntimeException('Uh-oh')]);
		$response = $this->appError->response($request);
		assert($response instanceof TextResponse && is_string($response->getSource()));
		Assert::contains('Donut worry', $response->getSource());
		Assert::type(RuntimeException::class, $this->logger->getLogged()[0]);
	}


	public function testForwardResponse(): void
	{
		$request = new Request('Foo:Bar:Name', params: ['exception' => new BadRequestException('Oh noes')]);
		$response = $this->appError->response($request);
		assert($response instanceof ForwardResponse);
		Assert::same('Foo:Bar:Error', $response->getRequest()->getPresenterName());
	}

}

TestCaseRunner::run(AppErrorTest::class);
