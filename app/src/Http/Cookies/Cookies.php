<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Cookies;

use MichalSpacekCz\DateTime\DateTimeFactory;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\Response;
use Nette\Http\SameSite;

final readonly class Cookies
{

	public function __construct(
		private IRequest $request,
		private IResponse $response,
		private DateTimeFactory $dateTimeFactory,
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
		string $expire,
		?string $path = null,
		?string $domain = null,
		?bool $secure = null,
		?bool $httpOnly = null,
		?SameSite $sameSite = null,
	): void {
		/** @var Response $response Not IResponse, its native setCookie() has no `$sameSite` until nette/http 4.0, see https://github.com/nette/http/issues/200, and Psalm ignores the `@method` that adds it; can't use instanceof check because it's a different Response in tests */
		$response = $this->response;
		$response->setCookie($name->value, $value, $this->dateTimeFactory->create($expire), $path, $domain, $secure, $httpOnly, $sameSite);
	}


	public function delete(CookieName $name, ?string $path = null, ?string $domain = null, ?bool $secure = null): void
	{
		$this->response->deleteCookie($name->value, $path, $domain, $secure);
	}

}
