<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use DateTime;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Html;
use Override;
use PDOException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class BlogPostTest extends TestCase
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
