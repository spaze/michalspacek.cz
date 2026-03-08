<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Theme;

use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class ThemeTest extends TestCase
{

	private const string COOKIE = 'future';


	public function __construct(
		private readonly Request $request,
		private readonly Response $response,
		private readonly Theme $theme,
	) {
	}


	public function testSetDarkMode(): void
	{
		$this->theme->setDarkMode();
		Assert::same('dark', $this->response->getCookie('future')[0]->getValue());
	}


	public function testSetLightMode(): void
	{
		$this->theme->setLightMode();
		Assert::same('light', $this->response->getCookie('future')[0]->getValue());
	}


	public function testCookieParams(): void
	{
		$this->response->cookiePath = '/foo';
		$this->response->cookieDomain = 'example.org';
		$this->theme->setDarkMode();
		$cookie = $this->response->getCookie('future')[0];
		Assert::same('future', $cookie->getName());
		Assert::true($cookie->getExpire() > time() + 364 * 24 * 60 * 60); // Using > and 364 days to avoid failures when time changes during the test execution
		Assert::true($cookie->isSecure());
		Assert::same('/foo', $cookie->getPath());
		Assert::same('example.org', $cookie->getDomain());
		Assert::same('None', $cookie->getSameSite());
	}


	public function testIsDarkMode(): void
	{
		Assert::null($this->theme->isDarkMode());
	}


	public function testIsDarkModeValueUnknown(): void
	{
		$this->request->setCookie(self::COOKIE, 'foo');
		Assert::null($this->theme->isDarkMode());
	}


	public function testIsDarkModeValueDark(): void
	{
		$this->request->setCookie(self::COOKIE, 'dark');
		Assert::true($this->theme->isDarkMode());
	}


	public function testIsDarkModeValueLegacy(): void
	{
		$this->request->setCookie(self::COOKIE, 'bright');
		Assert::false($this->theme->isDarkMode());
	}


	public function testIsDarkModeValueLight(): void
	{
		$this->request->setCookie(self::COOKIE, 'light');
		Assert::false($this->theme->isDarkMode());
	}

}

TestCaseRunner::run(ThemeTest::class);
