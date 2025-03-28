<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo\Exceptions;

use Exception;
use Throwable;

final class CompanyNotFoundException extends Exception
{

	public function __construct(?int $status = null, ?Throwable $previous = null)
	{
		parent::__construct($status !== null ? "Invalid status {$status}" : 'Company not found', previous: $previous);
	}

}
