<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Cookies;

use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Http\Helpers;
use Nette\Http\Session;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class CookieDescriptionsTest extends TestCase
{

	public function __construct(
		private readonly CookieDescriptions $cookieDescriptions,
		private readonly Session $sessionHandler,
		private readonly Request $request,
		private readonly Response $response,
	) {
	}


	public function testGet(): void
	{
		Helpers::initCookie($this->request, $this->response);
		$expectedCookieNames = array_map(fn(CookieName $cookieName): string => $cookieName->value, CookieName::cases());
		$expectedCookieNames[] = $this->sessionHandler->getName();
		$expectedCookieNames = array_merge($expectedCookieNames, $this->response->getCookieNames());
		$cookieDescriptions = $this->cookieDescriptions->get();
		$describedCookieNames = array_map(fn(CookieDescription $cookieDescription): string => $cookieDescription->getName(), $cookieDescriptions);
		Assert::same($expectedCookieNames, $describedCookieNames, 'All cookies must be described');
	}

}

TestCaseRunner::run(CookieDescriptionsTest::class);
