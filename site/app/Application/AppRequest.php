<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\Request;

class AppRequest
{

	public function getOriginalRequest(?Request $request): Request
	{
		if (!$request) {
			throw new ShouldNotHappenException('Request should be set before this method is called in UI\Presenter::run()');
		}
		$requestParam = $request->getParameter('request');
		if (!$requestParam instanceof Request) {
			throw new ShouldNotHappenException('No original request');
		}
		return $requestParam;
	}

}
