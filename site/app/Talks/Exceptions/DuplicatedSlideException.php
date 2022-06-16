<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Exceptions;

use Throwable;

class DuplicatedSlideException extends TalkException
{

	public function __construct(
		private readonly int $lastUniqueNumber,
		?Throwable $previous = null,
	) {
		parent::__construct("Duplicated slide, last unique is no. {$lastUniqueNumber}", $lastUniqueNumber, $previous);
	}


	public function getLastUniqueNumber(): int
	{
		return $this->lastUniqueNumber;
	}

}
