<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Exceptions;

use Throwable;

class TrainingDateNotRemoteNoVenueException extends TrainingDateException
{

	public function __construct(int $dateId, ?Throwable $previous = null)
	{
		parent::__construct("Training date id {$dateId} is not remote, but has no venue specified", previous: $previous);
	}

}
