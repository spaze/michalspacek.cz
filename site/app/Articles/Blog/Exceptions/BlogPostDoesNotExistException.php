<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog\Exceptions;

use Throwable;

class BlogPostDoesNotExistException extends BlogPostException
{

	public function __construct(?int $id = null, ?string $name = null, ?string $previewKey = null, ?Throwable $previous = null)
	{
		$message = 'Post';
		if ($id !== null) {
			$message .= " id {$id}";
		}
		if ($name !== null) {
			$message .= " name {$name}";
		}
		if ($previewKey !== null) {
			$message .= " preview key {$previewKey}";
		}
		parent::__construct("{$message} doesn't exist", previous: $previous);
	}

}
