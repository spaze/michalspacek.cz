<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use MichalSpacekCz\Database\TypedDatabase;

final readonly class BlogPostTranslation
{

	public function __construct(
		private TypedDatabase $database,
	) {
	}


	public function getNextTranslationId(): int
	{
		return $this->database->fetchFieldInt('SELECT COALESCE(MAX(key_translation_group), 0) FROM blog_posts') + 1;
	}

}
