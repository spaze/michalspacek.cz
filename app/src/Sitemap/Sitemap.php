<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Sitemap;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Caching\Cache;
use Nette\Caching\Storage;

final readonly class Sitemap
{

	private Cache $cache;


	/**
	 * @param list<SitemapEntries> $entries
	 */
	public function __construct(
		private SitemapActions $sitemapActions,
		private SitemapXml $sitemapXml,
		private LocaleLinkGenerator $localeLinkGenerator,
		private Translator $translator,
		private array $entries,
		Storage $cacheStorage,
	) {
		$this->cache = new Cache($cacheStorage, self::class);
	}


	/**
	 * The sitemap of the module's host in the current locale, with every page that exists in another locale too linked as its alternate.
	 */
	public function getXml(SitemapModule $module): string
	{
		$locale = $this->translator->getDefaultLocale();
		$xml = $this->cache->load([$module->value, $locale], function (array|null &$dependencies) use ($module, $locale): string {
			$dependencies[Cache::Expire] = '1 day';
			return $this->sitemapXml->create($locale, $this->getPages($module));
		});
		if (!is_string($xml)) {
			throw new ShouldNotHappenException(sprintf("Cached sitemap for %s in %s is a '%s' not a string", $module->value, $locale, get_debug_type($xml)));
		}
		return $xml;
	}


	/**
	 * @return array<string, list<SitemapEntry>> destination => its entries
	 */
	private function getPages(SitemapModule $module): array
	{
		$pages = [];
		foreach ($this->sitemapActions->getActions($module) as $action) {
			$inSitemap = $action->inSitemap[0] ?? null;
			if ($inSitemap === null) {
				continue;
			}
			$pages[$action->destination] = $inSitemap->entries === null
				? [new SitemapEntry($this->localeLinkGenerator->defaultParams([]))]
				: $this->getEntries($inSitemap->entries)->getEntries();
		}
		return $pages;
	}


	/**
	 * @param class-string<SitemapEntries> $class
	 */
	private function getEntries(string $class): SitemapEntries
	{
		foreach ($this->entries as $entries) {
			if ($entries::class === $class) {
				return $entries;
			}
		}
		throw new ShouldNotHappenException(sprintf('%s is not a service, register it in services.neon', $class));
	}

}
