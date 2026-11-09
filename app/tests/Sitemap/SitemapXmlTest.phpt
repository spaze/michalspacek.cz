<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Sitemap;

use DateTimeImmutable;
use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\Application\Routing\LocaleRouter;
use MichalSpacekCz\Test\NoOpTranslator;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\IPresenterFactory;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;
use Nette\Http\IRequest;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class SitemapXmlTest extends TestCase
{

	private SitemapXml $sitemapXml;


	public function __construct(
		private readonly NullLogger $logger,
		NoOpTranslator $translator,
		LocaleRouter $localeRouter,
		IRequest $httpRequest,
		IPresenterFactory $presenterFactory,
		LinkGenerator $linkGenerator,
	) {
		$localeLinkGenerator = new LocaleLinkGenerator($localeRouter, $httpRequest, $presenterFactory, $linkGenerator, $translator, [
			'cs_CZ' => ['code' => 'cs', 'name' => 'Česky'],
			'en_US' => ['code' => 'en', 'name' => 'English'],
		]);
		$this->sitemapXml = new SitemapXml($localeLinkGenerator);
	}


	public function testCreate(): void
	{
		$pages = [
			'Www:Talks:talk' => [
				new SitemapEntry(
					['cs_CZ' => ['name' => 'prednaska'], 'en_US' => ['name' => 'talk']],
					['cs_CZ' => new DateTimeImmutable('2026-11-07 10:20:30 +01:00'), 'en_US' => new DateTimeImmutable('2026-11-08 10:20:30 +01:00')],
				),
				new SitemapEntry(['en_US' => ['name' => 'english-only']]),
				new SitemapEntry(['cs_CZ' => ['name' => 'jen-cesky']]),
			],
			'Www:Who:default' => [
				new SitemapEntry(['*' => []]),
			],
		];
		$expected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
			. '<urlset xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
			. '<url><loc>https://www.rizek.test/prednasky/prednaska</loc><lastmod>2026-11-07T10:20:30+01:00</lastmod>'
			. '<xhtml:link rel="alternate" hreflang="cs" href="https://www.rizek.test/prednasky/prednaska"/>'
			. '<xhtml:link rel="alternate" hreflang="en" href="https://www.burger.test/talks/talk"/></url>'
			. '<url><loc>https://www.rizek.test/prednasky/jen-cesky</loc></url>'
			. '<url><loc>https://www.rizek.test/kdo</loc>'
			. '<xhtml:link rel="alternate" hreflang="cs" href="https://www.rizek.test/kdo"/>'
			. '<xhtml:link rel="alternate" hreflang="en" href="https://www.burger.test/who"/></url>'
			. "</urlset>\n";
		Assert::same($expected, $this->sitemapXml->create('cs_CZ', $pages));
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->logger->reset();
	}


	public function testCreateLeavesOutAnEntryThatCannotBeLinked(): void
	{
		$pages = [
			'Www:Talks:talk' => [
				new SitemapEntry(['cs_CZ' => ['slug' => 'wrong-parameter']]),
				new SitemapEntry(['cs_CZ' => ['name' => 'prednaska']]),
			],
		];
		$expected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
			. '<urlset xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
			. '<url><loc>https://www.rizek.test/prednasky/prednaska</loc></url>'
			. "</urlset>\n";
		Assert::same($expected, $this->sitemapXml->create('cs_CZ', $pages));
		$logged = $this->logger->getLogged();
		Assert::count(1, $logged);
		Assert::type(InvalidLinkException::class, $logged[0]);
	}


	/**
	 * An entry in other locales only isn't linked at all, so a link that can't be built isn't even tried.
	 */
	public function testCreateSkipsAnEntryInOtherLocalesOnly(): void
	{
		$pages = [
			'Www:Talks:talk' => [
				new SitemapEntry(['en_US' => ['slug' => 'wrong-parameter']]),
			],
		];
		Assert::notContains('<url>', $this->sitemapXml->create('cs_CZ', $pages));
		Assert::same([], $this->logger->getLogged());
	}


	public function testCreateEscapes(): void
	{
		$pages = [
			'Www:Tags:tag' => [
				new SitemapEntry(['cs_CZ' => ['tag' => 'a&b<c']]),
			],
		];
		Assert::contains('<loc>https://www.rizek.test/tema/a&amp;b%3Cc</loc>', $this->sitemapXml->create('cs_CZ', $pages));
	}

}

TestCaseRunner::run(SitemapXmlTest::class);
