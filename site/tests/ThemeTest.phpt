<?php
/** @noinspection PhpMissingParentConstructorInspection */
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz;

use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\UrlScript;
use stdClass;
use Tester\Assert;
use Tester\Environment;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

/**
 * @testCase MichalSpacekCz\ThemeTest
 */
class ThemeTest extends TestCase
{

	private Request $httpRequest;

	private Theme $theme;

	private stdClass $response;


	private function getThemeService(?string $requestCookieValue = null): Theme
	{
		$this->response = new stdClass();
		$cookies = $requestCookieValue ? ['future' => $requestCookieValue] : null;
		$httpRequest = new Request(new UrlScript(), null, null, $cookies);
		$httpResponse = new class ($this->response) extends Response {

			private stdClass $response;


			public function __construct(stdClass $response)
			{
				$this->response = $response;
			}


			public function setCookie(string $name, string $value, $time, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = null, string $sameSite = null)
			{
				$this->response->$name = $value;
			}

		};
		return new Theme($httpRequest, $httpResponse);
	}


	public function testSetDarkMode(): void
	{
		$theme = $this->getThemeService();
		$theme->setDarkMode();
		Assert::same('dark', $this->response->future);
	}


	public function testSetLightMode(): void
	{
		$theme = $this->getThemeService();
		$theme->setLightMode();
		Assert::same('bright', $this->response->future);
	}


	public function testIsDarkMode(): void
	{
		$theme = $this->getThemeService();
		Assert::null($theme->isDarkMode());

		$theme = $this->getThemeService('foo');
		Assert::null($theme->isDarkMode());

		$theme = $this->getThemeService('dark');
		Assert::true($theme->isDarkMode());

		$theme = $this->getThemeService('bright');
		Assert::false($theme->isDarkMode());

		$theme = $this->getThemeService('light');
		Assert::null($theme->isDarkMode());
	}

}

// Nette\Http\Response is marked as final and I can't be bothered implementing all the methods from IResponse
Environment::bypassFinals();
(new ThemeTest())->run();
