<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Feed;

use DateTime;
use MichalSpacekCz\Articles\ArticleEdit;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Test\Articles\ArticlesMock;
use MichalSpacekCz\Test\NoOpTranslator;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\BadRequestException;
use Nette\Caching\Storage;
use Nette\Utils\Html;
use Override;
use SimpleXMLElement;
use Spaze\Exports\Atom\Feed;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class ExportsTest extends TestCase
{

	private Exports $exports;


	public function __construct(
		private readonly ArticlesMock $articles,
		Storage $cacheStorage,
		NoOpTranslator $translator,
	) {
		$texyFormatter = new class () extends TexyFormatter {

			/** @noinspection PhpMissingParentConstructorInspection Intentionally */
			public function __construct()
			{
			}


			#[Override]
			public function translate(string $message, array $replacements = []): Html
			{
				return Html::el()->setHtml($message);
			}

		};
		$this->exports = new Exports($this->articles, $texyFormatter, $translator, $cacheStorage);
	}


	#[Override]
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
		$this->assertEntries('one', null, 'two', null);
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
		$this->assertEntries(
			'one',
			'<h3>messages.blog.post.edits</h3><ul><li><em><strong>14.3.</strong> Edit one one</em></li><li><em><strong>14.4.</strong> Edit one two</em></li></ul>Text one',
			'two',
			'<h3>messages.blog.post.edits</h3><ul><li><em><strong>15.3.</strong> Edit two one</em></li></ul>Text two',
		);
	}


	public function testGetArticlesOmitExports(): void
	{
		$this->articles->addBlogPost(1, new DateTime(), 'one');
		$this->articles->addBlogPost(2, new DateTime(), 'two', omitExports: true);
		$this->articles->addBlogPost(3, new DateTime(), 'three', omitExports: false);
		$this->assertEntries('one', null, 'three', null);
	}


	public function testGetArticlesOmitExportsOnly(): void
	{
		$this->articles->addBlogPost(1, new DateTime(), 'one', omitExports: true);
		$this->articles->addBlogPost(2, new DateTime(), 'two', omitExports: true);
		$this->articles->addBlogPost(3, new DateTime(), 'three', omitExports: true);
		[$feed, $count] = $this->getEntries();
		Assert::same(0, $count);
		Assert::null($feed->getUpdated());
	}


	/**
	 * @return array{0: Feed, 1:non-negative-int, 2: SimpleXMLElement|null, 3: SimpleXMLElement|null}
	 */
	private function getEntries(): array
	{
		$feed = $this->exports->getArticles('https://example.com/');
		$xml = simplexml_load_string((string)$feed);
		assert($xml instanceof SimpleXMLElement);
		assert($xml->entry instanceof SimpleXMLElement);
		return [$feed, count($xml->entry), $xml->entry[0], $xml->entry[1]];
	}


	private function assertEntries(string $suffix1, ?string $text1, string $suffix2, ?string $text2): void
	{
		[$feed, $count, $entry1, $entry2] = $this->getEntries();
		assert($entry1 instanceof SimpleXMLElement);
		assert($entry2 instanceof SimpleXMLElement);
		Assert::same(2, $count);
		Assert::same("https://example.com/$suffix1", (string)$entry1->id);
		Assert::same("Excerpt $suffix1", (string)$entry1->summary);
		Assert::same("Title $suffix1", (string)$entry1->title);
		Assert::same("https://example.com/$suffix1", $entry1->link !== null ? (string)$entry1->link['href'] : null);
		Assert::same($text1 ?? "Text $suffix1", (string)$entry1->content);
		Assert::same("https://example.com/$suffix2", (string)$entry2->id);
		Assert::same("Excerpt $suffix2", (string)$entry2->summary);
		Assert::same("Title $suffix2", (string)$entry2->title);
		Assert::same("https://example.com/$suffix2", $entry2->link !== null ? (string)$entry2->link['href'] : null);
		Assert::same($text2 ?? "Text $suffix2", (string)$entry2->content);
		Assert::notNull($feed->getUpdated());
	}


	private function buildEdit(string $editedAt, string $summary): ArticleEdit
	{
		return new ArticleEdit(
			new DateTime($editedAt),
			Html::el()->setHtml($summary),
			$summary,
		);
	}

}

TestCaseRunner::run(ExportsTest::class);
