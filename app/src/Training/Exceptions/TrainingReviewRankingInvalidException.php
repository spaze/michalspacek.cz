<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Exceptions;

use Throwable;

final class TrainingReviewRankingInvalidException extends TrainingException
{

	public function __construct(int $id, int $rating, ?Throwable $previous = null)
	{
		parent::__construct("The rating of the training review id '{$id}' is invalid: '{$rating}'", previous: $previous);
	}

}
