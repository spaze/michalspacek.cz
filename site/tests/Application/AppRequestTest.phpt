<?php
/** @noinspection PhpFullyQualifiedNameUsageInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use DateTime;
use Exception;
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


	/**
	 * @throws \MichalSpacekCz\ShouldNotHappenException Request should be set before this method is called in UI\Presenter::run()
	 */
	public function testGetOriginalRequestNoRequest(): void
	{
		$this->appRequest->getOriginalRequest(null);
	}


	/**
	 * @throws \MichalSpacekCz\ShouldNotHappenException No original request
	 */
	public function testGetOriginalRequestNoOriginalRequest(): void
	{
		$request = new Request('name');
		$request->setParameters(['request' => null]);
		$this->appRequest->getOriginalRequest($request);
	}


	/**
	 * @throws \MichalSpacekCz\ShouldNotHappenException No original request
	 */
	public function testGetOriginalRequestInvalidOriginalRequest(): void
	{
		$request = new Request('name');
		$request->setParameters(['request' => new DateTime()]);
		$this->appRequest->getOriginalRequest($request);
	}


	public function testGetOriginalRequest(): void
	{
		$original = new Request('bar');
		$request = new Request('foo');
		$request->setParameters(['request' => $original]);
		Assert::same($original, $this->appRequest->getOriginalRequest($request));
	}


	/**
	 * @throws \MichalSpacekCz\ShouldNotHappenException Not an exception
	 */
	public function testGetExceptionNoException(): void
	{
		$this->appRequest->getException(new Request('foo'));
	}


	/**
	 * @throws \MichalSpacekCz\ShouldNotHappenException Not an exception
	 */
	public function testGetExceptionNotAnException(): void
	{
		$request = new Request('foo');
		$request->setParameters([
			'exception' => null,
		]);
		$this->appRequest->getException($request);
	}


	/**
	 * @throws \MichalSpacekCz\ShouldNotHappenException Not an exception
	 */
	public function testGetExceptionInvalidException(): void
	{
		$request = new Request('foo');
		$request->setParameters([
			'exception' => new DateTime(),
		]);
		$this->appRequest->getException($request);
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
