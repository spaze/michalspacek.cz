<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Trainings;

use MichalSpacekCz\Training\TrainingLocales;

final readonly class SitemapTrainingsFactory
{

	public function __construct(
		private Trainings $trainings,
		private TrainingLocales $trainingLocales,
	) {
	}


	public function create(): SitemapTrainings
	{
		$allLocaleParams = $this->trainingLocales->getAllLocaleLinkParams();
		$localeParams = [];
		foreach ($this->trainings->getNames() as $training) {
			// None when its URLs were removed between the two reads, and a page in no locale isn't listed
			$localeParams[$training->getId()] = $allLocaleParams[$training->getId()] ?? [];
		}
		return new SitemapTrainings($localeParams);
	}

}
