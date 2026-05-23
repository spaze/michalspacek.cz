<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use DateTime;
use MichalSpacekCz\Articles\ArticleEdit;
use MichalSpacekCz\Articles\Components\ArticleWithId;
use MichalSpacekCz\Articles\Components\ArticleWithPublishTime;
use MichalSpacekCz\Articles\Components\ArticleWithSlug;
use MichalSpacekCz\Articles\Components\ArticleWithSummary;
use MichalSpacekCz\Articles\Components\ArticleWithTags;
use MichalSpacekCz\Articles\Components\ArticleWithTextAndEdits;
use MichalSpacekCz\Articles\Components\ArticleWithUpdateTime;
use MichalSpacekCz\Feed\ExportsOmittable;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Html;
use Override;
use PDOException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class BlogPostTest extends TestCase
{

	public function __construct(
		private readonly BlogPosts $blogPosts,
		private readonly Database $database,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testAdd(): void
	{
		$post = $this->buildBlogPost();
		$insertId = 1337;
		$this->database->setDefaultInsertId((string)$insertId);
		$insertedPost = $this->blogPosts->add($post);
		Assert::null($post->getId());
		Assert::same($insertId, $insertedPost->getId());
		Assert::count(1, $this->database->getParamsArrayForQuery('INSERT INTO blog_posts'));
	}


	public function testAddException(): void
	{
		$this->database->willThrow(new PDOException());
		Assert::exception(function (): void {
			$this->blogPosts->add($this->buildBlogPost());
		}, PDOException::class);
		Assert::count(0, $this->database->getParamsArrayForQuery('INSERT INTO blog_posts'));
	}


	public function testGetters(): void
	{
		$publishedAt = new DateTime('2024-01-01');
		$editedAt = new DateTime('2024-02-01');
		$title = Html::fromText('Title');
		$lead = Html::fromText('Lead');
		$text = Html::fromText('Text');
		$originally = Html::fromText('Originally');
		$edit = new ArticleEdit($editedAt, Html::fromText('Edit summary'), 'edit-summary');
		$tags = ['tag1', 'tag2'];
		$slugTags = ['slug-tag-1', 'slug-tag-2'];
		$post = new BlogPost(
			42,
			'hello',
			1,
			'en_US',
			null,
			$title,
			'title-texy',
			$lead,
			'lead-texy',
			$text,
			'text-texy',
			$publishedAt,
			false,
			null,
			$originally,
			'originally-texy',
			null,
			$tags,
			$slugTags,
			[],
			null,
			'/post/hello',
			[$edit],
			[],
			[],
			true,
		);

		Assert::same('en_US', $post->getLocale());
		Assert::same($originally, $post->getOriginally());

		// Helpers take the interface as their parameter type so the call inside resolves through the interface and the detector marks the interface method as used.
		// getLocale and getOriginally above aren't on any interface so they go through $post directly without needing a helper.
		$this->assertArticleWithId($post, 42, true);
		$this->assertArticleWithSlug($post, 'hello');
		$this->assertArticleWithSummary($post, $lead, true);
		$this->assertArticleWithTextAndEdits($post, $text, [$edit]);
		$this->assertArticleWithTags($post, $tags, $slugTags);
		$this->assertArticleWithPublishTime($post, $publishedAt);
		$this->assertArticleWithUpdateTime($post, $editedAt);
		$this->assertExportsOmittable($post, true);
	}


	private function assertArticleWithId(ArticleWithId $article, ?int $id, bool $hasId): void
	{
		Assert::same($hasId, $article->hasId());
		Assert::same($id, $article->getId());
	}


	private function assertArticleWithSlug(ArticleWithSlug $article, string $slug): void
	{
		Assert::same($slug, $article->getSlug());
	}


	private function assertArticleWithSummary(ArticleWithSummary $article, ?Html $summary, bool $hasSummary): void
	{
		Assert::same($hasSummary, $article->hasSummary());
		Assert::same($summary, $article->getSummary());
	}


	/**
	 * @param list<ArticleEdit> $edits
	 */
	private function assertArticleWithTextAndEdits(ArticleWithTextAndEdits $article, Html $text, array $edits): void
	{
		Assert::same($text, $article->getText());
		Assert::same($edits, $article->getEdits());
	}


	/**
	 * @param list<string> $tags
	 * @param list<string> $slugTags
	 */
	private function assertArticleWithTags(ArticleWithTags $article, array $tags, array $slugTags): void
	{
		Assert::same($tags, $article->getTags());
		Assert::same($slugTags, $article->getSlugTags());
	}


	private function assertArticleWithPublishTime(ArticleWithPublishTime $article, ?DateTime $published): void
	{
		Assert::same($published, $article->getPublishTime());
	}


	private function assertArticleWithUpdateTime(ArticleWithUpdateTime $article, ?DateTime $updated): void
	{
		Assert::same($updated, $article->getUpdateTime());
	}


	private function assertExportsOmittable(ExportsOmittable $exports, bool $omit): void
	{
		Assert::same($omit, $exports->omitExports());
	}


	/**
	 * @return BlogPost
	 */
	private function buildBlogPost(): BlogPost
	{
		$title = 'Title something';
		$text = 'Text something';
		return new BlogPost(
			null,
			'',
			2,
			'en_US',
			null,
			Html::fromText($title),
			$title,
			null,
			null,
			Html::fromText($text),
			$text,
			new DateTime(),
			false,
			null,
			null,
			null,
			null,
			[],
			[],
			[],
			null,
			'https://example.com/something',
			[],
			[],
			[],
			false,
		);
	}

}

TestCaseRunner::run(BlogPostTest::class);
