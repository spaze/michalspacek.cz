<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use Contributte\Translation\Translator;
use MichalSpacekCz\Database\TypedDatabase;

final class UpcomingTrainingDates
{

	/** @var array<int, array<string, UpcomingTraining>> */
	private array $upcomingDates = [];


	public function __construct(
		private readonly TypedDatabase $typedDatabase,
		private readonly Translator $translator,
		private readonly TrainingDateFactory $trainingDateFactory,
	) {
	}


	/**
	 * @return array<string, UpcomingTraining>
	 */
	public function getPublicUpcoming(): array
	{
		return $this->getUpcoming(false);
	}


	/**
	 * @return array<string, UpcomingTraining>
	 */
	public function getPublicUpcomingAtVenue(int $venueId): array
	{
		return $this->getUpcoming(false, $venueId);
	}


	/**
	 * @return list<int>
	 */
	public function getPublicUpcomingIds(): array
	{
		$upcomingIds = [];
		foreach ($this->getPublicUpcoming() as $training) {
			foreach ($training->getDates() as $date) {
				$upcomingIds[] = $date->getId();
			}
		}
		return $upcomingIds;
	}


	/**
	 * @return array<string, UpcomingTraining>
	 */
	public function getAllUpcoming(): array
	{
		return $this->getUpcoming(true);
	}


	/**
	 * @return array<string, UpcomingTraining>
	 */
	private function getUpcoming(bool $includeNonPublic, ?int $venueId = null): array
	{
		if (!isset($this->upcomingDates[(int)$includeNonPublic])) {
			$query = "SELECT
					d.id_date AS dateId,
					t.id_training AS trainingId,
					a.action,
					t.name,
					COALESCE(d.price, t.price) AS price,
					COALESCE(d.student_discount, t.student_discount) AS studentDiscount,
					d.price IS NOT NULL AS hasCustomPrice,
					d.student_discount IS NOT NULL AS hasCustomStudentDiscount,
					d.start,
					d.end,
					d.label AS labelJson,
					s.status,
					d.public,
					d.remote,
					d.remote_url AS remoteUrl,
					d.remote_notes AS remoteNotes,
					v.id_venue AS venueId,
					v.action AS venueAction,
					v.href AS venueHref,
					v.name AS venueName,
					v.name_extended AS venueNameExtended,
					v.address AS venueAddress,
					v.city AS venueCity,
					v.description AS venueDescription,
					c.id_cooperation AS cooperationId,
					c.description AS cooperationDescription,
					d.video_href AS videoHref,
					d.feedback_href AS feedbackHref,
					d.note
				FROM training_dates d
					JOIN trainings t ON d.key_training = t.id_training
					JOIN training_url_actions ta ON t.id_training = ta.key_training
					JOIN url_actions a ON ta.key_url_action = a.id_url_action
					JOIN languages l ON a.key_language = l.id_language
					JOIN training_date_status s ON d.key_status = s.id_status
					LEFT JOIN training_venues v ON d.key_venue = v.id_venue
				    LEFT JOIN training_cooperations c ON d.key_cooperation = c.id_cooperation
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
			foreach ($this->typedDatabase->fetchAll($query, $includeNonPublic, $includeNonPublic, TrainingDateStatus::Tentative->value, TrainingDateStatus::Confirmed->value, $this->translator->getDefaultLocale()) as $row) {
				if ($venueId !== null && $venueId !== $row->venueId) {
					continue;
				}
				$date = $this->trainingDateFactory->get($row);
				if (!isset($upcoming[$date->getAction()])) {
					$upcoming[$date->getAction()] = new UpcomingTraining($date->getAction(), $date->getName());
				}
				$upcoming[$date->getAction()]->addDate($date);
			}
			$this->upcomingDates[(int)$includeNonPublic] = $upcoming;
		}

		return $this->upcomingDates[(int)$includeNonPublic];
	}

}
