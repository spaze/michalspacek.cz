<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use DateTime;
use Exception;
use MichalSpacekCz\Application\Exceptions\NoOriginalRequestException;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\Request;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class AppRequestTest extends TestCase
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
		}, ShouldNotHappenException::class, "Not an exception");
	}


	public function testGetExceptionNotAnException(): void
	{
		$request = new Request('foo');
		$request->setParameters([
			'exception' => null,
		]);
		Assert::exception(function () use ($request): void {
			$this->appRequest->getException($request);
		}, ShouldNotHappenException::class, "Not an exception");
	}


	public function testGetExceptionInvalidException(): void
	{
		$request = new Request('foo');
		$request->setParameters([
			'exception' => new DateTime(),
		]);
		Assert::exception(function () use ($request): void {
			$this->appRequest->getException($request);
		}, ShouldNotHappenException::class, "Not an exception");
	}


	public function testGetException(): void
	{
		$e = new Exception();
		$request = new Request('foo');
		$request->setParameters([
			'exception' => $e,
		]);
		Assert::same($e, $this->appRequest->getException($request));
	}

}

$runner->run(AppRequestTest::class);
