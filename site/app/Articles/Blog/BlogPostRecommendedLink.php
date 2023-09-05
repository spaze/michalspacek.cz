<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use JsonSerializable;

class BlogPostRecommendedLink implements JsonSerializable
{

	public function __construct(
		private readonly string $url,
		private readonly string $text,
	) {
	}


	public function getUrl(): string
	{
		return $this->url;
	}


	public function getText(): string
	{
		return $this->text;
	}


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
