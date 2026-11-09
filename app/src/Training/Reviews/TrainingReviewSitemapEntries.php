<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Reviews;

use MichalSpacekCz\Sitemap\SitemapEntries;
use MichalSpacekCz\Sitemap\SitemapEntry;
use MichalSpacekCz\Training\Trainings\SitemapTrainings;
use Override;

/**
 * The reviews pages of the trainings in the sitemap that have a review to show; without one, the page only repeats
 * the training's description, and the training's page doesn't link to it either.
 */
final readonly class TrainingReviewSitemapEntries implements SitemapEntries
{

	public function __construct(
		private SitemapTrainings $sitemapTrainings,
		private TrainingReviews $trainingReviews,
	) {
	}


	#[Override]
	public function getEntries(): array
	{
		$reviewed = $this->trainingReviews->getTrainingIdsWithVisibleReviews();
		$entries = [];
		foreach ($this->sitemapTrainings->getLocaleParams() as $id => $localeParams) {
			if (in_array($id, $reviewed, true)) {
				$entries[] = new SitemapEntry($localeParams);
			}
		}
		return $entries;
	}

}
