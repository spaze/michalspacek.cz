<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Theme;

use MichalSpacekCz\Http\Cookies\CookieName;
use MichalSpacekCz\Http\Cookies\Cookies;
use ValueError;

final readonly class Theme
{

	public function __construct(
		private Cookies $cookies,
	) {
	}


	public function setDarkMode(): void
	{
		$this->setCookie(ThemeMode::Dark);
	}


	public function setLightMode(): void
	{
		$this->setCookie(ThemeMode::Light);
	}


	public function isDarkMode(): ?bool
	{
		$cookie = $this->cookies->getString(CookieName::Theme) ?? '';
		try {
			return ThemeMode::from($cookie) === ThemeMode::Dark;
		} catch (ValueError) {
			return null;
		}
	}


	private function setCookie(ThemeMode $mode): void
	{
		$this->cookies->set(CookieName::Theme, $mode->value, $this->getCookieLifetime(), sameSite: 'Lax');
	}


	public function getCookieLifetime(): string
	{
		return '365 days';
	}

}
