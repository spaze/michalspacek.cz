<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media\Exceptions;

use Nette\Utils\Json;
use Throwable;

final class ExtensionWithNoContentTypeException extends ContentTypeException
{

	/**
	 * @param non-empty-array<string, string> $supportedImages
	 */
	public function __construct(string $extension, array $supportedImages, ?Throwable $previous = null)
	{
		parent::__construct("Unsupported extension '{$extension}', available types are " . Json::encode($supportedImages), previous: $previous);
	}

}
