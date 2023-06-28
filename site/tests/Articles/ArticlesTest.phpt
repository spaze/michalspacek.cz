<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Articles;

use DateTime;
use MichalSpacekCz\Articles\Blog\BlogPost;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NoOpTranslator;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class ArticlesTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly Articles $articles,
		private readonly NoOpTranslator $translator,
	) {
	}


	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGetAll(): void
	{
		$fetchResult = [
			[
				'id' => 10,
				'localeId' => null,
				'translationGroupId' => null,
				'locale' => null,
				'titleTexy' => 'Article 1',
				'slug' => null,
				'href' => 'https://example.com/article-1',
				'published' => new DateTime('3 years ago'),
				'excerptTexy' => 'Excerpt 1',
				'textTexy' => null,
				'sourceName' => 'Source 1',
				'sourceHref' => 'https://source1.example/',
				'previewKey' => null,
				'originallyTexy' => null,
				'ogImage' => null,
				'tags' => null,
				'slugTags' => null,
				'recommended' => null,
				'cspSnippets' => null,
				'allowedTags' => null,
				'twitterCardId' => null,
				'omitExports' => null,
			],
			[
				'id' => 20,
				'localeId' => 1,
				'translationGroupId' => 3,
				'locale' => $this->translator->getDefaultLocale(),
				'titleTexy' => 'Blog 1',
				'slug' => 'blog-1',
				'href' => null,
				'published' => new DateTime('2 years ago'),
				'excerptTexy' => 'Lead 1',
				'textTexy' => 'Text 1',
				'sourceName' => null,
				'sourceHref' => null,
				'previewKey' => null,
				'originallyTexy' => null,
				'ogImage' => null,
				'tags' => null,
				'slugTags' => null,
				'recommended' => null,
				'cspSnippets' => null,
				'allowedTags' => null,
				'twitterCardId' => null,
				'omitExports' => null,
			],
			[
				'id' => 30,
				'localeId' => null,
				'translationGroupId' => null,
				'locale' => null,
				'titleTexy' => 'Article 2',
				'slug' => null,
				'href' => 'https://example.com/article-2',
				'published' => new DateTime('1 year ago'),
				'excerptTexy' => 'Excerpt 2',
				'textTexy' => null,
				'sourceName' => 'Source 2',
				'sourceHref' => 'https://source2.example/',
				'previewKey' => null,
				'originallyTexy' => null,
				'ogImage' => null,
				'tags' => null,
				'slugTags' => null,
				'recommended' => null,
				'cspSnippets' => null,
				'allowedTags' => null,
				'twitterCardId' => null,
				'omitExports' => null,
			],
			[
				'id' => 40,
				'localeId' => 1,
				'translationGroupId' => 5,
				'locale' => $this->translator->getDefaultLocale(),
				'titleTexy' => 'Blog 2',
				'slug' => 'blog-2',
				'href' => null,
				'published' => new DateTime('1 month ago'),
				'excerptTexy' => 'Lead 2',
				'textTexy' => 'Text 2',
				'sourceName' => null,
				'sourceHref' => null,
				'previewKey' => null,
				'originallyTexy' => null,
				'ogImage' => null,
				'tags' => null,
				'slugTags' => null,
				'recommended' => null,
				'cspSnippets' => null,
				'allowedTags' => null,
				'twitterCardId' => null,
				'omitExports' => null,
			],
		];
		$this->database->addFetchAllResult($fetchResult);
		$articles = $this->articles->getAll();
		Assert::type(ArticlePublishedElsewhere::class, $articles[0]);
		Assert::type(BlogPost::class, $articles[1]);
		Assert::type(ArticlePublishedElsewhere::class, $articles[2]);
		Assert::type(BlogPost::class, $articles[3]);
	}


	public function testGetNearestPublishDate(): void
	{
		$this->database->setFetchFieldResult(null);
		Assert::null($this->articles->getNearestPublishDate());

		$this->database->setFetchFieldResult(false);
		Assert::null($this->articles->getNearestPublishDate());

		$nearest = new DateTime('+3 days');
		$this->database->setFetchFieldResult($nearest);
		Assert::same($nearest, $this->articles->getNearestPublishDate());

		Assert::throws(function (): void {
			$this->database->setFetchFieldResult('\o/');
			$this->articles->getNearestPublishDate();
		}, ShouldNotHappenException::class, 'Nearest published date is a string not a DateTime object');
	}


	public function testGetNearestPublishDateByTags(): void
	{
		$this->database->setFetchFieldResult(null);
		Assert::null($this->articles->getNearestPublishDateByTags(['foo']));

		$this->database->setFetchFieldResult(false);
		Assert::null($this->articles->getNearestPublishDateByTags(['foo']));

		$nearest = new DateTime('+3 days');
		$this->database->setFetchFieldResult($nearest);
		Assert::same($nearest, $this->articles->getNearestPublishDateByTags(['foo']));

		Assert::throws(function (): void {
			$this->database->setFetchFieldResult('\o/');
			$this->articles->getNearestPublishDateByTags(['foo']);
		}, ShouldNotHappenException::class, 'Nearest published date is a string not a DateTime object');
	}

}

$runner->run(ArticlesTest::class);
