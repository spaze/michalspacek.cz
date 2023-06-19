<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Exceptions;

use MichalSpacekCz\Training\Dates\TrainingDate;
use Throwable;

class TrainingDateNotUpcomingException extends TrainingDateException
{

	/**
	 * @param int $dateId
	 * @param array<int, TrainingDate> $dates
	 * @param Throwable|null $previous
	 */
	public function __construct(int $dateId, array $dates, ?Throwable $previous = null)
	{
		parent::__construct(
			sprintf('Training date id %s is not an upcoming training, should be one of %s', $dateId, implode(', ', array_keys($dates))),
			previous: $previous,
		);
	}

}
