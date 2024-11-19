<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Exceptions;

use Exception;

class ParameterNotStringException extends Exception
{

	public function __construct(string $name, string $type)
	{
		parent::__construct("Component parameter '{$name}' is not a string but it's a {$type}");
	}

}
