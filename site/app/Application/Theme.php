<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Nette\Http\Request;
use Nette\Http\Response;

class Theme
{

	private const COOKIE = 'future';

	private const DARK = 'dark';

	private const LIGHT = 'bright';

	private Request $httpRequest;

	private Response $httpResponse;


	public function __construct(Request $httpRequest, Response $httpResponse)
	{
		$this->httpRequest = $httpRequest;
		$this->httpResponse = $httpResponse;
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
		$cookie = $this->httpRequest->getCookie(self::COOKIE);
		return $cookie === self::DARK ? true : ($cookie === self::LIGHT ? false : null);
	}


	private function setCookie(string $mode): void
	{
		$this->httpResponse->setCookie(self::COOKIE, $mode, '+10 years', null, null, null, null, 'None');
	}

}
