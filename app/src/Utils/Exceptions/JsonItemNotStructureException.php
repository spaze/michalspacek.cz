<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils\Exceptions;

use Exception;
use Throwable;

class JsonItemNotStructureException extends Exception
{

	/**
	 * @param list<string> $requiredFields
	 */
	public function __construct(mixed $item, array $requiredFields, string $json, ?Throwable $previous = null)
	{
		$message = sprintf("Item is of type '%s', not an array or object with required fields '%s' (JSON: '%s')", get_debug_type($item), implode("', '", $requiredFields), $json);
		parent::__construct($message, previous: $previous);
	}

}
