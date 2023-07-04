<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Exceptions;

use Throwable;

class TalkDoesNotExistException extends TalkException
{

	public function __construct(?int $id = null, ?string $name = null, ?Throwable $previous = null)
	{
		$message = "I haven't talked about";
		if ($id) {
			$message .= " id '{$id}'";
		}
		if ($name) {
			$message .= " name '{$name}'";
		}
		parent::__construct("{$message}, yet", previous: $previous);
	}

}
