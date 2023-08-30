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
class BlogPostRecommendedLinkTest extends TestCase
{

	public function testGetFromJson(): void
	{
		$link1 = new BlogPostRecommendedLink();
		$link1->url = 'https://example.com/';
		$link1->text = 'Link 1';
		$link2 = new BlogPostRecommendedLink();
		$link2->url = 'https://example.net/';
		$link2->text = 'Link 2';
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
