<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Talks\Exceptions;

use Exception;
use Throwable;

final class IncorrectSlideAliasInUrlException extends Exception
{

	public function __construct(
		public readonly ?string $correctAlias,
		int $code = 0,
		?Throwable $previous = null,
	) {
		parent::__construct('Incorrect slide alias in the page URL', $code, $previous);
	}

}
