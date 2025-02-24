<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Exceptions;

use Throwable;

final class TrainingDateDoesNotExistException extends TrainingDateException
{

	public function __construct(int $dateId, ?Throwable $previous = null)
	{
		parent::__construct("Training date id {$dateId} doesn't exist", previous: $previous);
	}

}
