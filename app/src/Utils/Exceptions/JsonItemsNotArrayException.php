<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils\Exceptions;

use Exception;
use Throwable;

final class JsonItemsNotArrayException extends Exception
{

	public function __construct(mixed $items, string $json, ?Throwable $previous = null)
	{
		parent::__construct(sprintf("The items array is actually a %s not an array (JSON: '%s')", get_debug_type($items), $json), previous: $previous);
	}

}
