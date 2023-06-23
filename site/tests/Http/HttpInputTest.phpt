<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Test\Http\Request;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class HttpInputTest extends TestCase
{

	public function __construct(
		private readonly Request $request,
		private readonly HttpInput $httpInput,
	) {
	}


	public function testGetCookieString(): void
	{
		Assert::null($this->httpInput->getCookieString('foo'));
		$this->request->setCookie('foo', 'bar');
		Assert::same('bar', $this->httpInput->getCookieString('foo'));
		Assert::with($this->request, function (): void {
			/** @noinspection PhpDynamicFieldDeclarationInspection $this is $this->request */
			$this->cookies = ['waldo' => ['quux' => 'foobar']];
		});
		Assert::null($this->httpInput->getCookieString('waldo'));
	}

}

$runner->run(HttpInputTest::class);
