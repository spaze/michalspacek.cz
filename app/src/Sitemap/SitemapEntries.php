<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Sitemap;

interface SitemapEntries
{

	/**
	 * @return list<SitemapEntry>
	 */
	public function getEntries(): array;

}
