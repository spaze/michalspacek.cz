<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Feed;

use DateTime;
use MichalSpacekCz\Articles\ArticleEdit;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Test\Articles\ArticlesMock;
use MichalSpacekCz\Test\NoOpTranslator;
use Nette\Application\BadRequestException;
use Nette\Caching\Storage;
use Nette\Utils\Html;
use SimpleXMLElement;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class ExportsTest extends TestCase
{

	private Exports $exports;


	public function __construct(
		private readonly ArticlesMock $articles,
		private readonly Storage $cacheStorage,
		private readonly NoOpTranslator $translator,
	) {
		$texyFormatter = new class () extends TexyFormatter {

			/** @noinspection PhpMissingParentConstructorInspection Intentionally */
			public function __construct()
			{
			}


			public function translate(string $message, array $replacements = []): Html
			{
				return Html::el()->setHtml($message);
			}

		};
		$this->exports = new Exports($this->articles, $texyFormatter, $this->translator, $this->cacheStorage);
	}


	protected function tearDown(): void
	{
		$this->articles->reset();
	}


	public function testGetArticlesNoArticles(): void
	{
		Assert::exception(function (): void {
			$this->exports->getArticles('https://example.com/no-articles');
		}, BadRequestException::class, "No articles");
	}


	public function testGetArticles(): void
	{
		$this->articles->addBlogPost(1, new DateTime(), 'one');
		$this->articles->addBlogPost(2, new DateTime(), 'two');
		$feed = $this->exports->getArticles('https://example.com/');
		$xml = simplexml_load_string((string)$feed);
		if (!$xml) {
			Assert::fail('Cannot load the feed');
		} else {
			$this->assertEntry($xml->entry[0], 'one');
			$this->assertEntry($xml->entry[1], 'two');
		}
	}


	public function testGetArticlesWithEdits(): void
	{
		$editsOne = [
			$this->buildEdit('2022-03-14 12:13:14 UTC', 'Edit one one'),
			$this->buildEdit('2022-04-14 12:13:14 UTC', 'Edit one two'),
		];
		$editsTwo = [
			$this->buildEdit('2022-03-15 12:13:14 UTC', 'Edit two one'),
		];
		$this->articles->addBlogPost(1, new DateTime(), 'one', $editsOne);
		$this->articles->addBlogPost(2, new DateTime(), 'two', $editsTwo);
		$feed = $this->exports->getArticles('https://example.com/');
		$xml = simplexml_load_string((string)$feed);
		if (!$xml) {
			Assert::fail('Cannot load the feed');
		} else {
			$this->assertEntry($xml->entry[0], 'one', '<h3>messages.blog.post.edits</h3><ul><li><em><strong>14.3.</strong> Edit one one</em></li><li><em><strong>14.4.</strong> Edit one two</em></li></ul>Text one');
			$this->assertEntry($xml->entry[1], 'two', '<h3>messages.blog.post.edits</h3><ul><li><em><strong>15.3.</strong> Edit two one</em></li></ul>Text two');
		}
	}


	public function testGetArticlesOmitExports(): void
	{
		$this->articles->addBlogPost(1, new DateTime(), 'one');
		$this->articles->addBlogPost(2, new DateTime(), 'two', omitExports: true);
		$this->articles->addBlogPost(3, new DateTime(), 'three', omitExports: false);
		$feed = $this->exports->getArticles('https://example.com/');
		$xml = simplexml_load_string((string)$feed);
		if (!$xml) {
			Assert::fail('Cannot load the feed');
		} else {
			$this->assertEntry($xml->entry[0], 'one');
			$this->assertEntry($xml->entry[1], 'three');
			Assert::count(2, $xml->entry);
			Assert::notNull($feed->getUpdated());
		}
	}


	public function testGetArticlesOmitExportsOnly(): void
	{
		$this->articles->addBlogPost(1, new DateTime(), 'one', omitExports: true);
		$this->articles->addBlogPost(2, new DateTime(), 'two', omitExports: true);
		$this->articles->addBlogPost(3, new DateTime(), 'three', omitExports: true);
		$feed = $this->exports->getArticles('https://example.com/');
		$xml = simplexml_load_string((string)$feed);
		if (!$xml) {
			Assert::fail('Cannot load the feed');
		} else {
			Assert::count(0, $xml->entry);
			Assert::null($feed->getUpdated());
		}
	}


	private function assertEntry(SimpleXMLElement $entry, string $suffix, ?string $text = null): void
	{
		$link = "https://example.com/{$suffix}";
		Assert::same($link, (string)$entry->id, $suffix);
		Assert::same("Excerpt {$suffix}", (string)$entry->summary, $suffix);
		Assert::same("Title {$suffix}", (string)$entry->title, $suffix);
		Assert::same($link, (string)$entry->link['href'], $suffix);
		Assert::same($text ?? "Text {$suffix}", (string)$entry->content, $suffix);
	}


	private function buildEdit(string $editedAt, string $summary): ArticleEdit
	{
		$edit = new ArticleEdit();
		$edit->editedAt = new DateTime($editedAt);
		$edit->summary = Html::el()->setHtml($summary);
		$edit->summaryTexy = $summary;
		return $edit;
	}

}

$runner->run(ExportsTest::class);
