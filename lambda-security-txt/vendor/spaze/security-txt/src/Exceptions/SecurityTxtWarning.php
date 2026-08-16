<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Exceptions;

use Exception;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;
use Throwable;

final class SecurityTxtWarning extends Exception
{

	public function __construct(private readonly SecurityTxtSpecViolation $violation, ?Throwable $previous = null)
	{
		parent::__construct($violation->getMessage(), previous: $previous);
	}


	public function getViolation(): SecurityTxtSpecViolation
	{
		return $this->violation;
	}

}
