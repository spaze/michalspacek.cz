<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog\Exceptions;

use Throwable;

class BlogPostWithoutIdException extends BlogPostException
{

	public function __construct(string $slug, ?Throwable $previous = null)
	{
		parent::__construct("Blog post {$slug} has no id", previous: $previous);
	}

}
