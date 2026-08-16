<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown when SecurityTxtNotFoundException could not be created because the URL structure was invalid. So meta.
 */
final class SecurityTxtNotFoundWrongUrlStructureException extends Exception
{

	public function __construct(string $message, ?Throwable $previous = null)
	{
		parent::__construct(sprintf('Cannot create %s: %s', SecurityTxtNotFoundException::class, $message), previous: $previous);
	}

}
