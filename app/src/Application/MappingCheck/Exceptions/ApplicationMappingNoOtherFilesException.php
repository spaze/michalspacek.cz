<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\MappingCheck\Exceptions;

use MichalSpacekCz\Application\MappingCheck\Files\ApplicationMappingCheckFile;
use Throwable;

class ApplicationMappingNoOtherFilesException extends ApplicationMappingException
{

	public function __construct(ApplicationMappingCheckFile $file, ?Throwable $previous = null)
	{
		parent::__construct("No other files with application mapping, just the primary one: '{$file->getFilename()}'", previous: $previous);
	}

}
