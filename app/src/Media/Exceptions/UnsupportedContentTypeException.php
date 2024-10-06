<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media\Exceptions;

use Nette\Utils\Json;
use Throwable;

class UnsupportedContentTypeException extends ContentTypeException
{

	/**
	 * @param non-empty-array<string, string> $supportedImages
	 */
	public function __construct(string $contentType, array $supportedImages, ?Throwable $previous = null)
	{
		parent::__construct("Unsupported content type '{$contentType}', available types are " . Json::encode($supportedImages), previous: $previous);
	}

}
