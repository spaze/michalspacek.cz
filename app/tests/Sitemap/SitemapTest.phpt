<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Sitemap;

use Composer\Pcre\Regex;
use DateTime;
use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\Application\Routing\LocaleRouter;
use MichalSpacekCz\Test\Articles\ArticlesMock;
use MichalSpacekCz\Test\Caching\MemoryStorageMock;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NoOpTranslator;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Reviews\TrainingReviews;
use MichalSpacekCz\Training\Reviews\TrainingReviewSitemapEntries;
use MichalSpacekCz\Training\Trainings\SitemapTrainingsFactory;
use MichalSpacekCz\Training\Trainings\TrainingSitemapEntries;
use Nette\Application\IPresenterFactory;
use Nette\Application\LinkGenerator;
use Nette\Caching\Cache;
use Nette\DI\Container;
use Nette\Http\IRequest;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/**
 * Mostly with no content in the database, so the pages are the actions without parameters. The entries are tested
 * on their own, and SitemapXmlTest tests how entries become the XML.
 *
 * @testCase
 */
final class SitemapTest extends TestCase
{

	private LocaleLinkGenerator $localeLinkGenerator;

	/** @var list<SitemapEntries> */
	private array $entries = [];

	private MemoryStorageMock $cacheStorage;


	public function __construct(
		private readonly SitemapActions $sitemapActions,
		private readonly NoOpTranslator $translator,
		private readonly ArticlesMock $articles,
		private readonly Database $database,
		private readonly NullLogger $logger,
		private readonly SitemapTrainingsFactory $sitemapTrainingsFactory,
		private readonly TrainingReviews $trainingReviews,
		LocaleRouter $localeRouter,
		IRequest $httpRequest,
		IPresenterFactory $presenterFactory,
		LinkGenerator $linkGenerator,
		Container $container,
	) {
		$this->localeLinkGenerator = new LocaleLinkGenerator($localeRouter, $httpRequest, $presenterFactory, $linkGenerator, $translator, [
			'cs_CZ' => ['code' => 'cs', 'name' => 'Česky'],
			'en_US' => ['code' => 'en', 'name' => 'English'],
		]);
		foreach ($container->findByType(SitemapEntries::class) as $name) {
			$service = $container->getService($name);
			assert($service instanceof SitemapEntries);
			$this->entries[] = $service;
		}
		$this->cacheStorage = new MemoryStorageMock();
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->cacheStorage->reset();
		$this->articles->reset();
		$this->database->reset();
		$this->logger->reset();
	}


	public function testGetXml(): void
	{
		Assert::same($this->getExpectedXml(), $this->createSitemap()->getXml(SitemapModule::Www));
	}


	public function testGetXmlCached(): void
	{
		$sitemap = $this->createSitemap();
		$xml = $sitemap->getXml(SitemapModule::Www);
		$this->articles->setAllTags(['foo' => 'Foo']);
		Assert::same($xml, $sitemap->getXml(SitemapModule::Www));
		$this->cacheStorage->reset();
		Assert::same($this->getExpectedXml('<url><loc>https://www.rizek.test/tema/foo</loc></url>'), $sitemap->getXml(SitemapModule::Www));
	}


	public function testGetXmlCachedPerModule(): void
	{
		$sitemap = $this->createSitemap();
		$www = $sitemap->getXml(SitemapModule::Www);
		Assert::same($this->getExpectedPulseXml(), $sitemap->getXml(SitemapModule::Pulse));
		Assert::same($www, $sitemap->getXml(SitemapModule::Www));
	}


	public function testGetXmlExpiresInADay(): void
	{
		$this->createSitemap()->getXml(SitemapModule::Www);
		$expire = $this->cacheStorage->getWrittenDependencies()[0][Cache::Expire];
		assert(is_int($expire));
		Assert::true($expire === 86400 || $expire === 86399, "Expires in {$expire} seconds"); // Cache counts from the real time(), a second may tick between the two
	}


	/**
	 * Every query gets the same row, with the columns all the entries read, so every action with entries has a page,
	 * and a parameter its action doesn't take would leave it out.
	 */
	public function testGetXmlEveryEntriesClassMakesAPage(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'id' => 1,
				'trainingId' => 1,
				'action' => 'action',
				'language' => 'cs_CZ',
				'locale' => 'cs_CZ',
				'translationGroupId' => 1,
				'slug' => 'slug',
				'slugTags' => '["foo"]',
				'published' => new DateTime('2026-11-07 10:20:30 +01:00'),
				'lastModified' => new DateTime('2026-11-07 10:20:30 +01:00'),
				'name' => 'Name',
				'company' => 'Company',
				'jobTitle' => null,
				'review' => 'Review',
				'href' => null,
				'hidden' => 0,
				'ranking' => null,
				'note' => null,
				'dateId' => 2,
				'description' => 'Description',
				'content' => 'Content',
				'upsell' => null,
				'prerequisites' => null,
				'audience' => null,
				'capacity' => null,
				'price' => null,
				'studentDiscount' => null,
				'materials' => null,
				'custom' => 0,
				'successorId' => null,
				'discontinuedId' => null,
			],
		]);
		$this->database->setFetchPairsDefaultResult(['cs_CZ' => 'action']);
		$this->articles->setAllTags(['foo' => 'Foo']);
		$expected = [
			'https://www.rizek.test/clanky',
			'https://www.rizek.test/firemni-skoleni',
			'https://www.rizek.test/firemni-skoleni/action',
			'https://www.rizek.test/kontakt',
			'https://www.rizek.test/cookies',
			'https://www.rizek.test/',
			'https://www.rizek.test/rozhovory',
			'https://www.rizek.test/rozhovory/action',
			'https://www.rizek.test/pgp',
			'https://www.rizek.test/foto',
			'https://www.rizek.test/slug',
			'https://www.rizek.test/projekty',
			'https://www.rizek.test/tema',
			'https://www.rizek.test/tema/foo',
			'https://www.rizek.test/prednasky',
			'https://www.rizek.test/prednasky/action',
			'https://www.rizek.test/skoleni',
			'https://www.rizek.test/skoleni/action',
			'https://www.rizek.test/skoleni/action/ohlasy',
			'https://www.rizek.test/kudy-tudy/action',
			'https://www.rizek.test/kdo',
		];
		Assert::same($expected, Regex::matchAll('~<loc>([^<]+)</loc>~', $this->createSitemap()->getXml(SitemapModule::Www))->matches[1]);
		Assert::same([], $this->logger->getLogged());
	}


	/**
	 * A module served in one locale has no alternates, and its pages aren't dropped for not having a link in another
	 * module's other locale.
	 */
	public function testGetXmlPulse(): void
	{
		Assert::same($this->getExpectedPulseXml(), $this->createSitemap()->getXml(SitemapModule::Pulse));
		Assert::same([], $this->logger->getLogged());
	}


	/**
	 * The sitemap is created first, the trainings list it builds reads rows of another shape.
	 */
	public function testGetXmlPulseEveryEntriesClassMakesAPage(): void
	{
		$sitemap = $this->createSitemap();
		$this->database->setFetchAllDefaultResult([['alias' => 'example']]);
		$expected = [
			'https://pulse.rizek.test/heartbleed',
			'https://pulse.rizek.test/',
			'https://pulse.rizek.test/passwords',
			'https://pulse.rizek.test/passwords/storages',
			'https://pulse.rizek.test/passwords/storages/site/example',
			'https://pulse.rizek.test/passwords/storages/company/example',
			'https://pulse.rizek.test/passwords/storages/rating',
			'https://pulse.rizek.test/passwords/storages/questions',
		];
		Assert::same($expected, Regex::matchAll('~<loc>([^<]+)</loc>~', $sitemap->getXml(SitemapModule::Pulse))->matches[1]);
		Assert::same([], $this->logger->getLogged());
	}


	/**
	 * The training entries share a list of the trainings, and the container keeps the one the first test built from its
	 * rows. So each sitemap gets training entries of its own, sharing a list built from the rows of its test.
	 */
	private function createSitemap(): Sitemap
	{
		$sitemapTrainings = $this->sitemapTrainingsFactory->create();
		$entries = [];
		foreach ($this->entries as $service) {
			$entries[] = match ($service::class) {
				TrainingSitemapEntries::class => new TrainingSitemapEntries($sitemapTrainings),
				TrainingReviewSitemapEntries::class => new TrainingReviewSitemapEntries($sitemapTrainings, $this->trainingReviews),
				default => $service,
			};
		}
		return new Sitemap($this->sitemapActions, new SitemapXml($this->localeLinkGenerator), $this->localeLinkGenerator, $this->translator, $entries, $this->cacheStorage);
	}


	private function getExpectedPulseXml(): string
	{
		$urls = '';
		foreach (['heartbleed', '', 'passwords', 'passwords/storages', 'passwords/storages/rating', 'passwords/storages/questions'] as $path) {
			$urls .= "<url><loc>https://pulse.rizek.test/{$path}</loc></url>";
		}
		return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
			. '<urlset xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
			. $urls
			. "</urlset>\n";
	}


	private function getExpectedXml(string $tagPages = ''): string
	{
		$pages = [
			'clanky' => 'articles',
			'firemni-skoleni' => 'inhouse-trainings',
			'kontakt' => 'contact',
			'cookies' => 'cookies',
			'' => '',
			'rozhovory' => 'interviews',
			'pgp' => 'pgp',
			'foto' => 'photo',
			'projekty' => 'projects',
			'tema' => 'tags',
			'prednasky' => 'talks',
			'skoleni' => 'trainings',
			'kdo' => 'who',
		];
		$urls = '';
		foreach ($pages as $cs => $en) {
			$urls .= sprintf(
				'<url><loc>https://www.rizek.test/%1$s</loc><xhtml:link rel="alternate" hreflang="cs" href="https://www.rizek.test/%1$s"/><xhtml:link rel="alternate" hreflang="en" href="https://www.burger.test/%2$s"/></url>',
				$cs,
				$en,
			);
			if ($cs === 'tema') {
				$urls .= $tagPages;
			}
		}
		return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
			. '<urlset xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
			. $urls
			. "</urlset>\n";
	}

}

TestCaseRunner::run(SitemapTest::class);
