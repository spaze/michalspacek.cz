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
			Html::fromText($title),
			$title,
			Html::fromText($lead),
			$lead,
			Html::fromText($text),
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
	}

}
