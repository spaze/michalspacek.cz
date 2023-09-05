<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Json;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class BlogPostRecommendedLinksTest extends TestCase
{

	public function __construct(
		private readonly BlogPostRecommendedLinks $recommendedLinks,
	) {
	}


	public function testGetFromJson(): void
	{
		$url1 = 'https://example.com/';
		$text1 = 'Dot.com';
		$url2 = 'https://com.example/';
		$text2 = 'Dot.example';
		$json = Json::encode([
			[
				'url' => $url1,
				'text' => $text1,
			],
			[
				'url' => $url2,
				'text' => $text2,
			],
		]);
		$links = $this->recommendedLinks->getFromJson($json);
		Assert::same($url1, $links[0]->getUrl());
		Assert::same($text1, $links[0]->getText());
		Assert::same($url2, $links[1]->getUrl());
		Assert::same($text2, $links[1]->getText());
	}


	public function testGetFromJsonMissingText(): void
	{
		$json = Json::encode([['url' => 'https://invalid.example/']]);
		Assert::exception(function () use ($json): void {
			$this->recommendedLinks->getFromJson($json);
		}, ShouldNotHappenException::class, 'Decoded data > link should have url and text keys, but has these: url');
	}


	public function testGetFromJsonMissingUrl(): void
	{
		$json = Json::encode([['text' => 'Invalid example']]);
		Assert::exception(function () use ($json): void {
			$this->recommendedLinks->getFromJson($json);
		}, ShouldNotHappenException::class, 'Decoded data > link should have url and text keys, but has these: text');
	}

}

TestCaseRunner::run(BlogPostRecommendedLinksTest::class);
