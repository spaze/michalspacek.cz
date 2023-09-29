<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Http\Cookies\CookieName;
use MichalSpacekCz\Http\Cookies\Cookies;

class Theme
{

	private const DARK = 'dark';

	private const LIGHT = 'bright';


	public function __construct(
		private readonly Cookies $cookies,
	) {
	}


	public function setDarkMode(): void
	{
		$this->setCookie(self::DARK);
	}


	public function setLightMode(): void
	{
		$this->setCookie(self::LIGHT);
	}


	public function isDarkMode(): ?bool
	{
		$cookie = $this->cookies->getString(CookieName::Theme);
		return $cookie === self::DARK ? true : ($cookie === self::LIGHT ? false : null);
	}


	private function setCookie(string $mode): void
	{
		$this->cookies->set(CookieName::Theme, $mode, $this->getCookieLifetime(), sameSite: 'None');
	}


	public function getCookieLifetime(): string
	{
		return '365 days';
	}

}
