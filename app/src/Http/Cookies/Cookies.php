<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Cookies;

use DateTimeInterface;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\Response;
use Nette\Utils\DateTime;

final readonly class Cookies
{

	public function __construct(
		private IRequest $request,
		private IResponse $response,
	) {
	}


	public function getString(CookieName $name): ?string
	{
		$cookie = $this->request->getCookie($name->value);
		if (!is_string($cookie)) {
			return null;
		}
		return $cookie;
	}


	public function set(
		CookieName $name,
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
		$response->setCookie($name->value, $value, (int)DateTime::from($expire)->format('U'), $path, $domain, $secure, $httpOnly, $sameSite);
	}


	public function delete(CookieName $name, ?string $path = null, ?string $domain = null, ?bool $secure = null): void
	{
		$this->response->deleteCookie($name->value, $path, $domain, $secure);
	}

}
