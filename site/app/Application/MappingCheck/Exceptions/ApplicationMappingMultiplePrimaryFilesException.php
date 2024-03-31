<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\MappingCheck\Exceptions;

use MichalSpacekCz\Application\MappingCheck\Files\ApplicationMappingCheckFile;
use Throwable;

class ApplicationMappingMultiplePrimaryFilesException extends ApplicationMappingException
{

	public function __construct(ApplicationMappingCheckFile $primary, ApplicationMappingCheckFile $other, ?Throwable $previous = null)
	{
		parent::__construct("Application mapping has multiple primary files: '{$primary->getFilename()}' & '{$other->getFilename()}'", previous: $previous);
	}

}
