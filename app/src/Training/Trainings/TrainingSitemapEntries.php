<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Trainings;

use MichalSpacekCz\Sitemap\SitemapEntries;
use MichalSpacekCz\Sitemap\SitemapEntry;
use Override;

/**
 * The training pages, a training and its translations as one entry.
 */
final readonly class TrainingSitemapEntries implements SitemapEntries
{

	public function __construct(
		private SitemapTrainings $sitemapTrainings,
	) {
	}


	#[Override]
	public function getEntries(): array
	{
		return array_values(array_map(fn(array $localeParams): SitemapEntry => new SitemapEntry($localeParams), $this->sitemapTrainings->getLocaleParams()));
	}

}
