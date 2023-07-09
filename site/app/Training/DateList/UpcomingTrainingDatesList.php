<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\DateList;

use MichalSpacekCz\Application\UiControl;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use MichalSpacekCz\Training\FreeSeats;

class UpcomingTrainingDatesList extends UiControl
{

	public function __construct(
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly FreeSeats $freeSeats,
		private readonly ?string $excludeTraining,
		private readonly bool $showLastFreeSeats,
		private readonly ?int $venueId = null,
	) {
	}


	public function render(): void
	{
		$upcomingTrainings = $this->venueId ? $this->upcomingTrainingDates->getPublicUpcomingAtVenue($this->venueId) : $this->upcomingTrainingDates->getPublicUpcoming();
		if ($this->excludeTraining) {
			unset($upcomingTrainings[$this->excludeTraining]);
		}
		$this->template->lastFreeSeats = $this->showLastFreeSeats && $this->freeSeats->lastFreeSeatsAnyTraining($upcomingTrainings);
		$this->template->upcomingTrainings = $upcomingTrainings;
		$this->template->render(__DIR__ . '/upcomingTrainingDatesList.latte');
	}

}
