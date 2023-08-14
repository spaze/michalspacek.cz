<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\UpcomingTraining;

class FreeSeats
{

	/**
	 * @param list<TrainingDate>|TrainingDate[] $dates
	 */
	public function lastFreeSeatsAnyDate(array $dates): bool
	{
		$lastFreeSeats = false;
		foreach ($dates as $date) {
			if ($date->isLastFreeSeats()) {
				$lastFreeSeats = true;
				break;
			}
		}
		return $lastFreeSeats;
	}


	/**
	 * @param list<UpcomingTraining>|UpcomingTraining[] $trainings array keys are irrelevant
	 */
	public function lastFreeSeatsAnyTraining(array $trainings): bool
	{
		$lastFreeSeats = false;
		foreach ($trainings as $training) {
			if ($this->lastFreeSeatsAnyDate($training->getDates())) {
				$lastFreeSeats = true;
				break;
			}
		}
		return $lastFreeSeats;
	}

}
