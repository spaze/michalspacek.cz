<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Exceptions;

use Throwable;

final class CompanyTrainingDoesNotExistException extends TrainingException
{

	public function __construct(string $name, ?Throwable $previous = null)
	{
		parent::__construct("Company training '{$name}' doesn't exist", previous: $previous);
	}

}
