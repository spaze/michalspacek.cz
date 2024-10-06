<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\MappingCheck\Exceptions;

use Throwable;

class ApplicationMappingNoPrimaryFileException extends ApplicationMappingException
{

	/**
	 * @param list<string> $files
	 */
	public function __construct(array $files, ?Throwable $previous = null)
	{
		parent::__construct("Application mapping has no primary file: '" . implode("', '", $files) . "'", previous: $previous);
	}

}
