<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use Contributte\Translation\Translator;
use MichalSpacekCz\Training\FreeSeats;
use Nette\Database\Explorer;
use Nette\Utils\ArrayHash;

class UpcomingTrainingDates
{

	/** @var array<int, array<string, ArrayHash>> */
	private array $upcomingDates = [];


	public function __construct(
		private readonly Explorer $database,
		private readonly Translator $translator,
		private readonly FreeSeats $freeSeats,
		private readonly TrainingDateLabel $dateLabel,
	) {
	}


	/**
	 * @return array<string, ArrayHash>
	 */
	public function getPublicUpcoming(): array
	{
		return $this->getUpcoming(false);
	}


	/**
	 * @return list<int>
	 */
	public function getPublicUpcomingIds(): array
	{
		$upcomingIds = [];
		foreach ($this->getPublicUpcoming() as $training) {
			foreach ($training->dates as $date) {
				$upcomingIds[] = $date->dateId;
			}
		}
		return $upcomingIds;
	}


	/**
	 * @return array<string, ArrayHash>
	 */
	public function getAllUpcoming(): array
	{
		return $this->getUpcoming(true);
	}


	/**
	 * @param bool $includeNonPublic
	 * @return array<string, ArrayHash>
	 */
	private function getUpcoming(bool $includeNonPublic): array
	{
		if (!isset($this->upcomingDates[(int)$includeNonPublic])) {
			$query = "SELECT
					d.id_date AS dateId,
					a.action,
					t.name,
					s.status,
					d.start,
					d.end,
					d.label AS labelJson,
					d.public,
					d.remote,
					v.id_venue AS venueId,
					v.name AS venueName,
					v.city AS venueCity,
					d.note
				FROM training_dates d
					JOIN trainings t ON d.key_training = t.id_training
					JOIN training_url_actions ta ON t.id_training = ta.key_training
					JOIN url_actions a ON ta.key_url_action = a.id_url_action
					JOIN languages l ON a.key_language = l.id_language
					JOIN training_date_status s ON d.key_status = s.id_status
					LEFT JOIN training_venues v ON d.key_venue = v.id_venue
					JOIN (
						SELECT
							t2.id_training,
							d2.key_venue,
							d2.start
						FROM
							trainings t2
							JOIN training_dates d2 ON t2.id_training = d2.key_training
							JOIN training_date_status s2 ON d2.key_status = s2.id_status
						WHERE
							(d2.public != ? OR TRUE = ?)
							AND d2.end > NOW()
							AND s2.status IN (?, ?)
					) u ON t.id_training = u.id_training AND (v.id_venue = u.key_venue OR u.key_venue IS NULL) AND d.start = u.start
				WHERE
					t.key_successor IS NULL
					AND t.key_discontinued IS NULL
					AND l.language = ?
				ORDER BY
					d.start";

			$upcoming = [];
			foreach ($this->database->fetchAll($query, $includeNonPublic, $includeNonPublic, TrainingDateStatus::Tentative->value, TrainingDateStatus::Confirmed->value, $this->translator->getDefaultLocale()) as $row) {
				$date = [
					'dateId' => $row->dateId,
					'tentative' => $row->status === TrainingDateStatus::Tentative->value,
					'lastFreeSeats' => $this->freeSeats->lastFreeSeats($row),
					'start' => $row->start,
					'end' => $row->end,
					'label' => $this->dateLabel->decodeLabel($row->labelJson),
					'public' => $row->public,
					'status' => $row->status,
					'name' => $this->translator->translate($row->name),
					'remote' => (bool)$row->remote,
					'venueId' => $row->venueId,
					'venueName' => $row->venueName,
					'venueCity' => $row->venueCity,
					'note' => $row->note,
				];
				/** @var string $action */
				$action = $row->action;
				$upcoming[$action] = ArrayHash::from([
					'action' => $action,
					'name' => $date['name'],
					'dates' => (isset($upcoming[$action]->dates)
						? $upcoming[$action]->dates = (array)$upcoming[$action]->dates + [$row->dateId => $date]
						: [$row->dateId => $date]
					),
				]);
			}
			$this->upcomingDates[(int)$includeNonPublic] = $upcoming;
		}

		return $this->upcomingDates[(int)$includeNonPublic];
	}

}
