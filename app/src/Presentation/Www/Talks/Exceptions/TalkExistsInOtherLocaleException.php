<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Talks\Exceptions;

use Exception;
use Throwable;

final class TalkExistsInOtherLocaleException extends Exception
{

	public function __construct(
		public readonly string $locale,
		int $code = 0,
		?Throwable $previous = null,
	) {
		parent::__construct("The talk exists in locale '{$locale}'", $code, $previous);
	}

}
