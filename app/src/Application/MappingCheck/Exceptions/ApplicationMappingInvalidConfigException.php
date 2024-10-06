<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\MappingCheck\Exceptions;

use Throwable;

class ApplicationMappingInvalidConfigException extends ApplicationMappingException
{

	public function __construct(string $file, string $message, ?Throwable $previous = null)
	{
		parent::__construct("Application mapping config invalid in '{$file}': {$message}", previous: $previous);
	}

}
