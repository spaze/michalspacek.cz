<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Exceptions;

use Exception;
use Throwable;

class TrainingStatusIdNotIntException extends Exception
{

	public function __construct(string $status, mixed $id, ?Throwable $previous = null)
	{
		parent::__construct(sprintf("Training status '%s' id is a %s not an integer", $status, get_debug_type($id)), previous: $previous);
	}

}
