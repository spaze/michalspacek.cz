<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\MappingCheck\Exceptions;

use MichalSpacekCz\Application\MappingCheck\Files\ApplicationMappingCheckFile;
use Throwable;

class ApplicationMappingMismatchException extends ApplicationMappingException
{

	public function __construct(ApplicationMappingCheckFile $primary, ApplicationMappingCheckFile $file, ?Throwable $previous = null)
	{
		$message = sprintf(
			"Application mapping in '%s' ('%s') doesn't match the primary mapping in '%s' ('%s')",
			$file->getFilename(),
			$this->describeMapping($file),
			$primary->getFilename(),
			$this->describeMapping($primary),
		);
		parent::__construct($message, previous: $previous);
	}


	private function describeMapping(ApplicationMappingCheckFile $file): string
	{
		$result = [];
		foreach ($file->getMapping() as $key => $value) {
			$result[] = "{$key}: {$value}";
		}
		return implode('; ', $result);
	}

}
