<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Application\Exceptions\NoOriginalRequestException;
use MichalSpacekCz\Application\Exceptions\ParameterNotStringException;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\Request;
use Throwable;

class AppRequest
{

	/**
	 * @throws NoOriginalRequestException
	 */
	public function getOriginalRequest(?Request $request): Request
	{
		if (!$request) {
			throw new ShouldNotHappenException('Request should be set before this method is called in UI\Presenter::run()');
		}
		$requestParam = $request->getParameter('request');
		if (!$requestParam instanceof Request) {
			throw new NoOriginalRequestException();
		}
		return $requestParam;
	}


	/**
	 * @return array<string, string|null>
	 * @throws NoOriginalRequestException
	 * @throws ParameterNotStringException
	 */
	public function getOriginalRequestStringParameters(?Request $request): array
	{
		$params = [];
		foreach ($this->getOriginalRequest($request)->getParameters() as $name => $value) {
			$name = (string)$name;
			if ($value === null || is_string($value)) {
				$params[$name] = $value;
			} else {
				throw new ParameterNotStringException($name, get_debug_type($value));
			}
		}
		return $params;
	}


	public function getException(Request $request): Throwable
	{
		$e = $request->getParameter('exception');
		if (!$e instanceof Throwable) {
			throw new ShouldNotHappenException('Neither an exception nor an error');
		}
		return $e;
	}

}
