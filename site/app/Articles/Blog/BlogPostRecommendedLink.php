<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use JsonSerializable;
use Override;

readonly class BlogPostRecommendedLink implements JsonSerializable
{

	public function __construct(
		private string $url,
		private string $text,
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
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'url' => $this->url,
			'text' => $this->text,
		];
	}

}
