<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use DateTime;
use MichalSpacekCz\Training\Dates\TrainingDateStatus;
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
	 * @param Row[] $dates
	 * @return bool
	 */
	public function lastFreeSeatsAnyDate(array $dates): bool
	{
		$lastFreeSeats = false;
		foreach ($dates as $date) {
			if ($date->lastFreeSeats) {
				$lastFreeSeats = true;
				break;
			}
		}
		return $lastFreeSeats;
	}

}
