<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\DateList;

interface UpcomingTrainingDatesListFactory
{

	public function create(?string $excludeTraining, bool $showLastFreeSeats, ?int $venueId = null): UpcomingTrainingDatesList;

}
