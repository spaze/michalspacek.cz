<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use Nette\Http\IRequest;

class HttpInput
{

	public function __construct(
		private readonly IRequest $request,
	) {
	}


	public function getCookieString(string $key): ?string
	{
		$cookie = $this->request->getCookie($key);
		if (!is_string($cookie)) {
			return null;
		}
		return $cookie;
	}

}
