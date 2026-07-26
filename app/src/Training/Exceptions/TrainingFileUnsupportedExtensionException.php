<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Exceptions;

use RuntimeException;
use Throwable;

final class TrainingFileUnsupportedExtensionException extends RuntimeException
{

	/**
	 * @param list<string> $allowedExtensions
	 */
	public function __construct(string $name, array $allowedExtensions, ?string $mimeType, ?Throwable $previous = null)
	{
		$message = sprintf(
			"Unsupported training file extension of '%s' (%s), allowed extensions: %s",
			$name,
			$mimeType ?? 'unknown type',
			implode(', ', $allowedExtensions),
		);
		parent::__construct($message, previous: $previous);
	}

}
