<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Exceptions;

use Exception;
use Spaze\Encryption\Format\LogSafeValue;
use Throwable;

class InvalidKeyIdException extends Exception
{

	public function __construct(string $id, string $keyCipherTextSeparator, ?Throwable $previous = null)
	{
		parent::__construct($id === '' ? 'Key id must not be empty' : "Key id '" . LogSafeValue::from($id) . "' must not contain '{$keyCipherTextSeparator}'", previous: $previous);
	}

}
