<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Json;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class BlogPostRecommendedLinkTest extends TestCase
{

	public function testGetFromJson(): void
	{
		$link1 = new BlogPostRecommendedLink('https://example.com/', 'Link 1');
		$link2 = new BlogPostRecommendedLink('https://example.net/', 'Link 2');
		$expected = Json::encode([
			[
				'url' => 'https://example.com/',
				'text' => 'Link 1',
			],
			[
				'url' => 'https://example.net/',
				'text' => 'Link 2',
			],
		]);
		Assert::same($expected, Json::encode([$link1, $link2]));
	}

}

TestCaseRunner::run(BlogPostRecommendedLinkTest::class);
