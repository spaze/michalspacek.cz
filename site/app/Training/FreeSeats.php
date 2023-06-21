<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use DateTime;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\TrainingDateStatus;
use MichalSpacekCz\Training\Dates\UpcomingTraining;
use Nette\Database\Row;

class FreeSeats
{

	private const LAST_FREE_SEATS_THRESHOLD_DAYS = 7;


	/**
	 * @param Row<mixed> $date
	 * @return bool
	 */
	public function lastFreeSeats(Row $date): bool
	{
		$now = new DateTime();
		return ($date->start->diff($now)->days <= self::LAST_FREE_SEATS_THRESHOLD_DAYS && $date->start > $now && $date->status !== TrainingDateStatus::Tentative->value);
	}


	/**
	 * @param list<TrainingDate>|TrainingDate[] $dates
	 * @return bool
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
	 * @return bool
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
