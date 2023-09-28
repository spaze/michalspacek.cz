<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Cookies;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Http\Session;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class CookieDescriptionsTest extends TestCase
{

	public function __construct(
		private readonly CookieDescriptions $cookieDescriptions,
		private readonly Session $sessionHandler,
	) {
	}


	public function testGet(): void
	{
		$expectedCookieNames = array_map(fn(CookieName $cookieName): string => $cookieName->value, CookieName::cases());
		$expectedCookieNames[] = $this->sessionHandler->getName();
		$expectedCookieNames[] = '_nss';
		$cookieDescriptions = $this->cookieDescriptions->get();
		$describedCookieNames = array_map(fn(CookieDescription $cookieDescription): string => $cookieDescription->getName(), $cookieDescriptions);
		Assert::same($expectedCookieNames, $describedCookieNames, 'All cookies must be described');
	}

}

TestCaseRunner::run(CookieDescriptionsTest::class);
