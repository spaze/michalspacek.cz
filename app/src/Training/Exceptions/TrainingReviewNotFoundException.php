<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Exceptions;

use Throwable;

final class TrainingReviewNotFoundException extends TrainingException
{

	public function __construct(int $id, ?Throwable $previous = null)
	{
		parent::__construct("Training review id '{$id}' doesn't exist", previous: $previous);
	}

}
