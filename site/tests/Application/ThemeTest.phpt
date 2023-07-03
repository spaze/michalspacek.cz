<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\Http\Response;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class ThemeTest extends TestCase
{

	private const COOKIE = 'future';


	public function __construct(
		private readonly Request $request,
		private readonly Response $response,
		private readonly Theme $theme,
	) {
	}


	public function testSetDarkMode(): void
	{
		$this->theme->setDarkMode();
		Assert::same('dark', $this->response->getCookie('future')?->getValue());
	}


	public function testSetLightMode(): void
	{
		$this->theme->setLightMode();
		Assert::same('bright', $this->response->getCookie('future')?->getValue());
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


	public function testIsDarkModeValueBright(): void
	{
		$this->request->setCookie(self::COOKIE, 'bright');
		Assert::false($this->theme->isDarkMode());
	}


	public function testIsDarkModeValueLight(): void
	{
		$this->request->setCookie(self::COOKIE, 'light');
		Assert::null($this->theme->isDarkMode());
	}

}

$runner->run(ThemeTest::class);
