<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Interviews;

use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\Sitemap\SitemapEntries;
use MichalSpacekCz\Sitemap\SitemapEntry;
use Nette\Database\Explorer;
use Override;

/**
 * Every interview, each one a page in every locale.
 */
final readonly class InterviewSitemapEntries implements SitemapEntries
{

	public function __construct(
		private Explorer $database,
		private LocaleLinkGenerator $localeLinkGenerator,
	) {
	}


	#[Override]
	public function getEntries(): array
	{
		$entries = [];
		foreach ($this->database->fetchAll('SELECT action FROM interviews ORDER BY date DESC') as $row) {
			assert(is_string($row->action));
			$entries[] = new SitemapEntry($this->localeLinkGenerator->defaultParams(['name' => $row->action]));
		}
		return $entries;
	}

}
