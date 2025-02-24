<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Articles;

use MichalSpacekCz\Articles\Blog\BlogPost;
use MichalSpacekCz\Articles\Blog\BlogPostFactory;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Tags\Tags;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NoOpTranslator;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\DateTime;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class ArticlesTest extends TestCase
{

	private Articles $articles;


	public function __construct(
		private readonly Database $database,
		TypedDatabase $typedDatabase,
		private readonly NoOpTranslator $translator,
		TexyFormatter $texyFormatter,
		BlogPostFactory $blogPostFactory,
		Tags $tags,
	) {
		$this->articles = new Articles(
			$this->database,
			$typedDatabase,
			$texyFormatter,
			$blogPostFactory,
			$tags,
			$this->translator,
		);
	}


	#[Override]
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
				'leadTexy' => "Excerpt 1\n#########\nFoo",
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
				'twitterCard' => null,
				'twitterCardTitle' => null,
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
				'leadTexy' => 'Lead 1',
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
				'twitterCard' => null,
				'twitterCardTitle' => null,
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
				'leadTexy' => 'Excerpt 2',
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
				'twitterCard' => null,
				'twitterCardTitle' => null,
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
				'leadTexy' => 'Lead 2',
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
				'twitterCard' => null,
				'twitterCardTitle' => null,
				'omitExports' => null,
			],
		];
		$this->database->addFetchAllResult($fetchResult);
		$articles = $this->articles->getAll();
		Assert::type(ArticlePublishedElsewhere::class, $articles[0]);
		Assert::same("<h2 id=\"excerpt-1\">Excerpt 1</h2>\n\n<p>Foo</p>\n", $articles[0]->getSummary()?->render());
		Assert::type(BlogPost::class, $articles[1]);
		Assert::type(ArticlePublishedElsewhere::class, $articles[2]);
		Assert::type(BlogPost::class, $articles[3]);
	}


	public function testGetNearestPublishDate(): void
	{
		$this->database->setFetchFieldDefaultResult(null);
		Assert::null($this->articles->getNearestPublishDate());

		$nearest = new \Nette\Utils\DateTime('+3 days');
		$this->database->setFetchFieldDefaultResult($nearest);
		Assert::same($nearest, $this->articles->getNearestPublishDate());
	}


	public function testGetNearestPublishDateByTags(): void
	{
		$this->database->setFetchFieldDefaultResult(null);
		Assert::null($this->articles->getNearestPublishDateByTags(['foo']));

		$nearest = new DateTime('+3 days');
		$this->database->setFetchFieldDefaultResult($nearest);
		Assert::same($nearest, $this->articles->getNearestPublishDateByTags(['foo']));
	}


	public function testGetLabelByTag(): void
	{
		Assert::null($this->articles->getLabelByTag('foo'));
		$this->database->setFetchDefaultResult([
			'tags' => '["HTTP Secure", "HTTP/2"]',
			'slugTags' => '["https", "http-2"]',
		]);
		Assert::same('HTTP Secure', $this->articles->getLabelByTag('https'));
		Assert::same('HTTP/2', $this->articles->getLabelByTag('http-2'));
		Assert::null($this->articles->getLabelByTag('http-1337'));
	}

}

TestCaseRunner::run(ArticlesTest::class);
