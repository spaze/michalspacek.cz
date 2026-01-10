<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use MichalSpacekCz\Articles\Blog\BlogPostTranslation;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class BlogPostTranslationTest extends TestCase
{

	public function __construct(
		private readonly BlogPostTranslation $blogPostTranslation,
		private readonly Database $database,
	) {
	}


	public function testGetNextTranslationId(): void
	{
		$this->database->setFetchFieldDefaultResult(0);
		Assert::same(1, $this->blogPostTranslation->getNextTranslationId());

		$this->database->setFetchFieldDefaultResult(1337);
		Assert::same(1338, $this->blogPostTranslation->getNextTranslationId());
	}

}

TestCaseRunner::run(BlogPostTranslationTest::class);
