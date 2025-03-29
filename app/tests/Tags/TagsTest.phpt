<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tags;

use DateTime;
use MichalSpacekCz\Articles\ArticlePublishedElsewhere;
use MichalSpacekCz\Articles\Blog\BlogPost;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Html;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class TagsTest extends TestCase
{

	private const string SLUG_TAG_EN = 'something';
	private const string SLUG_TAG_CS = 'neco';


	public function __construct(
		private readonly Tags $tags,
		private readonly Database $database,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testToArray(): void
	{
		Assert::same(['foo', 'bar'], $this->tags->toArray(",foo \t, bar,\t"));
		Assert::same([], $this->tags->toArray(''));
		Assert::same([], $this->tags->toArray(' ,, '));
	}


	public function testToString(): void
	{
		Assert::same('foo, bar', $this->tags->toString(['foo', 'bar']));
	}


	public function testToSlugArray(): void
	{
		Assert::same(['foo', 'bar-waldo'], $this->tags->toSlugArray(",fóo \t, Bař walďo,\t"));
	}


	public function testSerialize(): void
	{
		Assert::same('["foo","bar"]', $this->tags->serialize(['foo', 'bar']));
	}


	public function testUnserialize(): void
	{
		Assert::same(['foo', 'bar'], $this->tags->unserialize('["foo","bar"]'));
	}


	public function testFindLocaleLinkParams(): void
	{
		$this->database->addFetchAllResult([
			[
				'locale' => 'en_US',
				'slug' => '',
				'published' => null,
				'previewKey' => null,
				'slugTags' => $this->tags->serialize([self::SLUG_TAG_EN]),
			],
			[
				'locale' => 'cs_CZ',
				'slug' => '',
				'published' => null,
				'previewKey' => null,
				'slugTags' => $this->tags->serialize([self::SLUG_TAG_CS]),
			],
		]);
		$expected = [
			'en_US' => ['tag' => self::SLUG_TAG_EN],
			'cs_CZ' => ['tag' => self::SLUG_TAG_CS],
		];
		$articles = [
			$this->buildArticlePublishedElsewhere(),
			$this->buildBlogPost(),
		];
		Assert::same($expected, $this->tags->findLocaleLinkParams($articles, self::SLUG_TAG_EN));
	}


	public function testFindLocaleLinkParamsNotTranslated(): void
	{
		$this->database->addFetchAllResult([
			[
				'locale' => 'en_US',
				'slug' => '',
				'published' => null,
				'previewKey' => null,
				'slugTags' => $this->tags->serialize([self::SLUG_TAG_EN]),
			],
		]);
		Assert::same([], $this->tags->findLocaleLinkParams([$this->buildBlogPost()], self::SLUG_TAG_EN));
	}


	private function buildArticlePublishedElsewhere(): ArticlePublishedElsewhere
	{
		$title = 'Article something';
		$excerpt = 'Excerpt something';
		return new ArticlePublishedElsewhere(
			7,
			Html::fromText($title),
			$title,
			'',
			new DateTime(),
			Html::fromText($excerpt),
			$excerpt,
			'',
			'',
		);
	}


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
			['Something'],
			[self::SLUG_TAG_EN],
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

TestCaseRunner::run(TagsTest::class);
