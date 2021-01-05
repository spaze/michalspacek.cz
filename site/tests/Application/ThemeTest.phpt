<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\ServicesTrait;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class ThemeTest extends TestCase
{

	use ServicesTrait;


	private const COOKIE = 'future';


	private Request $request;

	private Theme $theme;


	protected function setUp()
	{
		$this->request = $this->getHttpRequest();
		$this->theme = $this->getTheme();
	}


	public function testSetDarkMode(): void
	{
		$this->theme->setDarkMode();
		Assert::same('dark', $this->getHttpResponse()->getCookie('future'));
	}


	public function testSetLightMode(): void
	{
		$this->theme->setLightMode();
		Assert::same('bright', $this->getHttpResponse()->getCookie('future'));
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

(new ThemeTest())->run();
