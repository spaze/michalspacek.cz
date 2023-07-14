<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Reviews;

use Nette\Database\Row;

interface TrainingReviewInputsFactory
{

	public function create(bool $showApplications, int $dateId, ?Row $review = null): TrainingReviewInputs;

}
