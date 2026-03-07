<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Talks\Exceptions;

use Exception;
use Throwable;

final class DeprecatedEmbedSlideInUrlException extends Exception
{

	public function __construct(
		int $code = 0,
		?Throwable $previous = null,
	) {
		parent::__construct('Slide of the deprecated slide embed in the page URL', $code, $previous);
	}

}
