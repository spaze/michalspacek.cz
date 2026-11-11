<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\Sitemap\SitemapEntries;
use MichalSpacekCz\Sitemap\SitemapEntry;
use Nette\Database\Explorer;
use Override;

/**
 * Every site with a storage of its own or of its company's, joined like Passwords::getStoragesBySite() joins them,
 * so every site listed has a page.
 */
final readonly class StorageSiteSitemapEntries implements SitemapEntries
{

	public function __construct(
		private Explorer $database,
		private LocaleLinkGenerator $localeLinkGenerator,
	) {
	}


	#[Override]
	public function getEntries(): array
	{
		$query = 'SELECT DISTINCT s.alias
			FROM companies c
				JOIN sites s ON s.key_companies = c.id
				JOIN password_storages ps ON ps.key_sites = s.id OR ps.key_companies = c.id
				JOIN password_disclosures_password_storages pdps ON pdps.key_password_storages = ps.id
			ORDER BY s.alias';
		$entries = [];
		foreach ($this->database->fetchAll($query) as $row) {
			assert(is_string($row->alias));
			$entries[] = new SitemapEntry($this->localeLinkGenerator->defaultParams(['param' => $row->alias]));
		}
		return $entries;
	}

}
