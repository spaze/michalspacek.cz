<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class HttpInputTest extends TestCase
{

	public function __construct(
		private readonly Request $request,
		private readonly HttpInput $httpInput,
	) {
	}


	public function testGetPostString(): void
	{
		Assert::null($this->httpInput->getPostString('foo'));
		$this->request->setPost('foo', 'bar');
		Assert::same('bar', $this->httpInput->getPostString('foo'));
		$this->request->setPost('waldo', ['quux' => 'foobar']);
		Assert::null($this->httpInput->getPostString('waldo'));
	}


	public function testGetPostArray(): void
	{
		Assert::null($this->httpInput->getPostArray('foo'));
		$this->request->setPost('foo', 'bar');
		Assert::null($this->httpInput->getPostArray('foo'));
		$this->request->setPost('waldo', ['quux' => 'foobar']);
		Assert::same(['quux' => 'foobar'], $this->httpInput->getPostArray('waldo'));
	}

}

TestCaseRunner::run(HttpInputTest::class);
