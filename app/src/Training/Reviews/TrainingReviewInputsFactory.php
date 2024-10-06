<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Reviews;

interface TrainingReviewInputsFactory
{

	public function create(bool $showApplications, int $dateId, ?TrainingReview $review = null): TrainingReviewInputs;

}
