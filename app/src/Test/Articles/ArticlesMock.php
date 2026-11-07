<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Articles;

use DateTime;
use MichalSpacekCz\Articles\ArticleEdit;
use MichalSpacekCz\Articles\Articles;
use MichalSpacekCz\Articles\Blog\BlogPost;
use Nette\Utils\Html;
use Override;

final class ArticlesMock extends Articles
{

	/** @var list<BlogPost> */
	private array $articles = [];

	/** @var array<string, string> */
	private array $tags = [];


	/**
	 * @noinspection PhpMissingParentConstructorInspection Intentionally
	 * @phpstan-ignore constructor.missingParentCall
	 */
	public function __construct()
	{
	}


	#[Override]
	public function getNearestPublishDate(): ?DateTime
	{
		return null;
	}


	/**
	 * @return array<string, string>
	 */
	#[Override]
	public function getAllTags(): array
	{
		return $this->tags;
	}


	/**
	 * @param array<string, string> $tags slug => label
	 */
	public function setAllTags(array $tags): void
	{
		$this->tags = $tags;
	}


	/**
	 * @param list<ArticleEdit> $edits
	 */
	public function addBlogPost(int $postId, DateTime $published, string $suffix, array $edits = [], bool $omitExports = false): void
	{
		$title = "Title {$suffix}";
		$lead = "Excerpt {$suffix}";
		$text = "Text {$suffix}";
		$post = new BlogPost(
			$postId,
			'',
			1,
			'en_US',
			null,
			Html::text($title),
			$title,
			Html::text($lead),
			$lead,
			Html::text($text),
			$text,
			$published,
			false,
			null,
			null,
			null,
			null,
			[],
			[],
			[],
			null,
			"https://example.com/{$suffix}",
			$edits,
			[],
			[],
			$omitExports,
		);
		$this->articles[] = $post;
	}


	/**
	 * @return list<BlogPost>
	 * @throws void
	 */
	#[Override]
	public function getAll(?int $limit = null): array
	{
		return $this->articles;
	}


	public function reset(): void
	{
		$this->articles = [];
		$this->tags = [];
	}

}
