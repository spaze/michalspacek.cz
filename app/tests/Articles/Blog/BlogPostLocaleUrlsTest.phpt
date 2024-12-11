<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use DateTime;
use MichalSpacekCz\DateTime\DateTimeFormat;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class BlogPostLocaleUrlsTest extends TestCase
{

	public function __construct(
		private readonly BlogPostLocaleUrls $blogPostLocaleUrls,
		private readonly Database $database,
	) {
	}


	public function testGet(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'locale' => 'cs_CZ',
				'slug' => 'le-blog-post',
				'published' => new DateTime('2020-05-06 07:08:09'),
				'previewKey' => 'rand0m',
				'slugTags' => '["foo","bar"]',
			],
		]);
		$urls = $this->blogPostLocaleUrls->get('le-blog-post');
		Assert::count(1, $urls);
		Assert::same('2020-05-06T07:08:09.000000+02:00', $urls[0]->getPublished()?->format(DateTimeFormat::RFC3339_MICROSECONDS));
		Assert::same('cs_CZ', $urls[0]->getLocale());
		Assert::null($urls[0]->getPreviewKey());
		Assert::same('le-blog-post', $urls[0]->getSlug());
		Assert::same(['foo', 'bar'], $urls[0]->getSlugTags());
	}


	/**
	 * @return array<string, array{0:string|null, 1:bool}>
	 */
	public function getPublished(): array
	{
		return [
			'no published' => [
				null,
				true,
			],
			'future' => [
				'7 days + 1 week', // Remember B.B.E.?
				true,
			],
			'past' => [
				'-7 years -50 days', // Or Groove Coverage?
				false,
			],
		];
	}


	/**
	 * @dataProvider getPublished
	 */
	public function testGetPreviewKey(?string $published, bool $hasPreviewKey): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'locale' => 'cs_CZ',
				'slug' => 'le-blog-post',
				'published' => $published !== null ? new DateTime($published) : null,
				'previewKey' => 'rand0m',
				'slugTags' => null,
			],
		]);
		$urls = $this->blogPostLocaleUrls->get('le-blog-post');
		if ($hasPreviewKey) {
			Assert::same('rand0m', $urls[0]->getPreviewKey());
		} else {
			Assert::null($urls[0]->getPreviewKey());
		}
	}

}

TestCaseRunner::run(BlogPostLocaleUrlsTest::class);
