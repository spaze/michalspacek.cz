<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Sitemap;

use DateTimeInterface;
use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use Nette\Application\UI\InvalidLinkException;
use Tracy\Debugger;
use XMLWriter;

final readonly class SitemapXml
{

	public function __construct(
		private LocaleLinkGenerator $localeLinkGenerator,
	) {
	}


	/**
	 * The sitemap of the locale's host: the pages that exist in the locale, each linked to its other locales as alternates.
	 * An entry that can't be linked is logged and left out, so one bad row doesn't take the whole sitemap down.
	 *
	 * @param array<string, list<SitemapEntry>> $pages destination => its entries
	 */
	public function create(string $locale, array $pages): string
	{
		$writer = new XMLWriter();
		$writer->openMemory();
		$writer->startDocument('1.0', 'UTF-8');
		$writer->startElementNs(null, 'urlset', 'http://www.sitemaps.org/schemas/sitemap/0.9');
		$writer->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
		foreach ($pages as $destination => $entries) {
			foreach ($entries as $entry) {
				if (!$this->localeLinkGenerator->hasLocale($entry->localeParams, $locale)) {
					continue;
				}
				try {
					$links = $this->localeLinkGenerator->linksForLocaleParams($destination, $entry->localeParams);
				} catch (InvalidLinkException $e) {
					Debugger::log($e);
					continue;
				}
				$writer->startElement('url');
				$writer->writeElement('loc', $links[$locale]->getUrl());
				if (isset($entry->lastModified[$locale])) {
					$writer->writeElement('lastmod', $entry->lastModified[$locale]->format(DateTimeInterface::ATOM));
				}
				if (count($links) > 1) {
					foreach ($links as $link) {
						$writer->startElement('xhtml:link');
						$writer->writeAttribute('rel', 'alternate');
						$writer->writeAttribute('hreflang', $link->getLanguageCode());
						$writer->writeAttribute('href', $link->getUrl());
						$writer->endElement();
					}
				}
				$writer->endElement();
			}
		}
		$writer->endElement();
		$writer->endDocument();
		return $writer->outputMemory();
	}

}
