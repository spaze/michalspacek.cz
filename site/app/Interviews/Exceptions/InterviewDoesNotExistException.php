<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Interviews\Exceptions;

use Throwable;

class InterviewDoesNotExistException extends InterviewException
{

	public function __construct(?int $id = null, ?string $name = null, ?Throwable $previous = null)
	{
		$message = 'Interview';
		if ($id) {
			$message .= " id {$id}";
		}
		if ($name) {
			$message .= " name {$name}";
		}
		parent::__construct("{$message} doesn't exist", previous: $previous);
	}

}