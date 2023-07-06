<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Http\HttpInput;
use Nette\Http\IResponse;
use Nette\Http\Response;

class Theme
{

	private const COOKIE = 'future';

	private const DARK = 'dark';

	private const LIGHT = 'bright';


	public function __construct(
		private readonly HttpInput $httpInput,
		private readonly IResponse $httpResponse,
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
		$cookie = $this->httpInput->getCookieString(self::COOKIE);
		return $cookie === self::DARK ? true : ($cookie === self::LIGHT ? false : null);
	}


	private function setCookie(string $mode): void
	{
		/** @var Response $response Not IResponse because https://github.com/nette/http/issues/200, can't use instanceof check because it's a different Response in tests */
		$response = $this->httpResponse;
		$response->setCookie(self::COOKIE, $mode, '+10 years', null, null, null, null, 'None');
	}

}
