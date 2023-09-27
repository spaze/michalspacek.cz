<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Cookies;

use DateTimeInterface;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\Response;

class Cookies
{

	public function __construct(
		private readonly IRequest $request,
		private readonly IResponse $response,
	) {
	}


	public function getString(string $key): ?string
	{
		$cookie = $this->request->getCookie($key);
		if (!is_string($cookie)) {
			return null;
		}
		return $cookie;
	}


	public function set(
		string $name,
		string $value,
		DateTimeInterface|int|string $expire,
		?string $path = null,
		?string $domain = null,
		?bool $secure = null,
		?bool $httpOnly = null,
		?string $sameSite = null,
	): void {
		/** @var Response $response Not IResponse because https://github.com/nette/http/issues/200, can't use instanceof check because it's a different Response in tests */
		$response = $this->response;
		$response->setCookie($name, $value, $expire, $path, $domain, $secure, $httpOnly, $sameSite);
	}


	public function delete(string $name, ?string $path = null, ?string $domain = null, ?bool $secure = null): void
	{
		$this->response->deleteCookie($name, $path, $domain, $secure);
	}

}
