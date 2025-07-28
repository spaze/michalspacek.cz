<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Exceptions;

use RuntimeException;
use Spaze\SubresourceIntegrity\HashingAlgo;
use Throwable;

class HashFileException extends RuntimeException
{

	public function __construct(HashingAlgo $algo, string $filename, ?Throwable $previous = null)
	{
		parent::__construct(sprintf('Cannot generate a %s hash of %s', $algo->value, $filename), previous: $previous);
	}

}
