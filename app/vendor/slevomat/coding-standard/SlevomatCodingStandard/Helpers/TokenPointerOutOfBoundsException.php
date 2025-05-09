<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Helpers;

use Exception;
use Throwable;
use function sprintf;

/**
 * @internal
 */
class TokenPointerOutOfBoundsException extends Exception
{

	private int $pointer;

	private int $lastTokenPointer;

	public function __construct(int $pointer, int $lastTokenPointer, ?Throwable $previous = null)
	{
		parent::__construct(
			sprintf(
				'Attempted access to token pointer %d, last token pointer is %d',
				$pointer,
				$lastTokenPointer,
			),
			0,
			$previous,
		);

		$this->pointer = $pointer;
		$this->lastTokenPointer = $lastTokenPointer;
	}

	public function getPointer(): int
	{
		return $this->pointer;
	}

	public function getLastTokenPointer(): int
	{
		return $this->lastTokenPointer;
	}

}
