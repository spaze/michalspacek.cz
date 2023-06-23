<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use JsonSerializable;

class BlogPostRecommendedLink implements JsonSerializable
{

	public string $url;
	public string $text;


	/**
	 * @return array{url:string, text:string}
	 */
	public function jsonSerialize(): array
	{
		return [
			'url' => $this->url,
			'text' => $this->text,
		];
	}

}
