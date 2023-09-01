<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\DateList;

use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use MichalSpacekCz\Training\FreeSeats;

class UpcomingTrainingDatesListFactory
{

	public function __construct(
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly FreeSeats $freeSeats,
	) {
	}


	public function create(): UpcomingTrainingDatesList
	{
		return new UpcomingTrainingDatesList($this->upcomingTrainingDates, $this->freeSeats, null, true, null);
	}


	public function createExclude(?string $excludeTraining): UpcomingTrainingDatesList
	{
		return new UpcomingTrainingDatesList($this->upcomingTrainingDates, $this->freeSeats, $excludeTraining, false, null);
	}


	public function createForVenue(int $venueId): UpcomingTrainingDatesList
	{
		return new UpcomingTrainingDatesList($this->upcomingTrainingDates, $this->freeSeats, null, true, $venueId);
	}

}
