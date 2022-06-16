<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Test\Http\Response as TestResponse;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\Response;

class Theme
{

	private const COOKIE = 'future';

	private const DARK = 'dark';

	private const LIGHT = 'bright';


	public function __construct(
		private readonly IRequest $httpRequest,
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
		$cookie = $this->httpRequest->getCookie(self::COOKIE);
		return $cookie === self::DARK ? true : ($cookie === self::LIGHT ? false : null);
	}


	private function setCookie(string $mode): void
	{
		/** @var Response|TestResponse $response */
		$response = $this->httpResponse;
		$response->setCookie(self::COOKIE, $mode, '+10 years', null, null, null, null, 'None');
	}

}
