<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Exceptions;

use Throwable;

final class TrainingApplicationDoesNotExistException extends TrainingApplicationException
{

	public function __construct(int $id, ?Throwable $previous = null)
	{
		parent::__construct("Training application id {$id} doesn't exist", previous: $previous);
	}

}
