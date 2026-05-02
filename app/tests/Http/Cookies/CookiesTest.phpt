<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Cookies;

use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class CookiesTest extends TestCase
{

	public function __construct(
		private readonly Request $request,
		private readonly Cookies $cookies,
	) {
	}


	public function testGetString(): void
	{
		Assert::null($this->cookies->getString(CookieName::Theme));
		$this->request->setCookie(CookieName::Theme->value, 'bar');
		Assert::same('bar', $this->cookies->getString(CookieName::Theme));
	}

}

TestCaseRunner::run(CookiesTest::class);
