<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Components;

interface ArticleWithTags
{

	/**
	 * @return list<string>
	 */
	public function getTags(): array;


	/**
	 * @return list<string>
	 */
	public function getSlugTags(): array;

}
