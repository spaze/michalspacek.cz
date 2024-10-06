<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Makefile\Exceptions;

use Throwable;

class MakefileNotFoundException extends MakefileException
{

	public function __construct(string $file, ?Throwable $previous = null)
	{
		parent::__construct("Makefile '{$file}' not found", previous: $previous);
	}

}
