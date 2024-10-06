<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\MappingCheck\Exceptions;

use Throwable;

class ApplicationMappingFileNotFoundException extends ApplicationMappingException
{

	public function __construct(string $file, ?Throwable $previous = null)
	{
		parent::__construct("Application mapping file not found: '{$file}'", previous: $previous);
	}

}
