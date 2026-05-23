<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Articles;

use DateTime;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class ArticlePublishedElsewhereTest extends TestCase
{

	public function testGetters(): void
	{
		$article = new ArticlePublishedElsewhere(
			42,
			Html::fromText('Title'),
			'title-texy',
			'https://example.com/article',
			new DateTime('2024-01-01'),
			Html::fromText('Excerpt'),
			'excerpt-texy',
			'Source Name',
			'https://example.com/source',
		);
		Assert::same('title-texy', $article->getTitleTexy());
		Assert::same('excerpt-texy', $article->getSummaryTexy());
		Assert::same('Source Name', $article->getSourceName());
		Assert::same('https://example.com/source', $article->getSourceHref());
	}

}

TestCaseRunner::run(ArticlePublishedElsewhereTest::class);
