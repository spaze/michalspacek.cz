<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DependencyInjection\Exceptions;

use Exception;
use Throwable;

class DiServicesConfigInvalidException extends Exception
{

	public function __construct(?string $file, ?string $section, string $message, ?Throwable $previous = null)
	{
		$ident = $file !== null ? "{$file}:" : '';
		if ($section !== null) {
			$ident .= "{$section}:";
		}
		parent::__construct("{$ident} {$message}", previous: $previous);
	}

}
