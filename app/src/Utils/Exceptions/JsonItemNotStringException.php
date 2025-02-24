<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils\Exceptions;

use Exception;
use Throwable;

final class JsonItemNotStringException extends Exception
{

	public function __construct(mixed $item, string $json, ?Throwable $previous = null)
	{
		parent::__construct(sprintf("Item is of type '%s', not a string (JSON: '%s')", get_debug_type($item), $json), previous: $previous);
	}

}
