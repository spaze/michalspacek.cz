<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Api\Endpoint;

use MichalSpacekCz\Presentation\Api\BasePresenter;
use ReflectionAttribute;
use ReflectionClass;

final class EndpointAccess
{

	/**
	 * Whether the endpoint declares its access with exactly one EndpointAccessAttribute. False for none
	 * (undeclared) or more than one (a contradictory declaration).
	 */
	public static function isDeclared(BasePresenter $presenter): bool
	{
		return count(new ReflectionClass($presenter)->getAttributes(EndpointAccessAttribute::class, ReflectionAttribute::IS_INSTANCEOF)) === 1;
	}

}
