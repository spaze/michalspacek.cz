<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Exceptions;

use Throwable;

class TrainingDoesNotExistException extends TrainingException
{

	public function __construct(?int $id = null, ?string $name = null, ?Throwable $previous = null)
	{
		$message = 'Training';
		if ($id) {
			$message .= " id {$id}";
		}
		if ($name) {
			$message .= " name {$name}";
		}
		parent::__construct("{$message} doesn't exist", previous: $previous);
	}

}
