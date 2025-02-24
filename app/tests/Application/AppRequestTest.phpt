<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use DateTime;
use Error;
use Exception;
use MichalSpacekCz\Application\Exceptions\NoOriginalRequestException;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Request;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class AppRequestTest extends TestCase
{

	public function __construct(
		private readonly AppRequest $appRequest,
	) {
	}


	public function testGetOriginalRequestNoRequest(): void
	{
		Assert::exception(function (): void {
			$this->appRequest->getOriginalRequest(null);
		}, ShouldNotHappenException::class, 'Request should be set before this method is called in UI\Presenter::run()');
	}


	public function testGetOriginalRequestNoOriginalRequest(): void
	{
		$request = new Request('name');
		$request->setParameters(['request' => null]);
		Assert::exception(function () use ($request): void {
			$this->appRequest->getOriginalRequest($request);
		}, NoOriginalRequestException::class);
	}


	public function testGetOriginalRequestInvalidOriginalRequest(): void
	{
		$request = new Request('name');
		$request->setParameters(['request' => new DateTime()]);
		Assert::exception(function () use ($request): void {
			$this->appRequest->getOriginalRequest($request);
		}, NoOriginalRequestException::class);
	}


	public function testGetOriginalRequest(): void
	{
		$original = new Request('bar');
		$request = new Request('foo');
		$request->setParameters(['request' => $original]);
		Assert::same($original, $this->appRequest->getOriginalRequest($request));
	}


	public function testGetExceptionNoException(): void
	{
		Assert::exception(function (): void {
			$this->appRequest->getException(new Request('foo'));
		}, ShouldNotHappenException::class, 'Neither an exception nor an error');
	}


	public function testGetExceptionNotAnException(): void
	{
		$request = new Request('foo');
		$request->setParameters([
			'exception' => null,
		]);
		Assert::exception(function () use ($request): void {
			$this->appRequest->getException($request);
		}, ShouldNotHappenException::class, 'Neither an exception nor an error');
	}


	public function testGetExceptionInvalidException(): void
	{
		$request = new Request('foo');
		$request->setParameters([
			'exception' => new DateTime(),
		]);
		Assert::exception(function () use ($request): void {
			$this->appRequest->getException($request);
		}, ShouldNotHappenException::class, 'Neither an exception nor an error');
	}


	public function testGetException(): void
	{
		$e = new Exception();
		$request = new Request('foo');
		$request->setParameters([
			'exception' => $e,
		]);
		Assert::same($e, $this->appRequest->getException($request));

		$e = new Error();
		$request->setParameters([
			'exception' => $e,
		]);
		Assert::same($e, $this->appRequest->getException($request));
	}

}

TestCaseRunner::run(AppRequestTest::class);
