<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use DateTime;
use DateTimeImmutable;
use Nette\Database\Context;
use Nette\Database\Row;
use Nette\Localization\ITranslator;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;

class Dates
{

	public const STATUS_CREATED   = 'CREATED';    // 1
	public const STATUS_TENTATIVE = 'TENTATIVE';  // 2
	public const STATUS_CONFIRMED = 'CONFIRMED';  // 3
	public const STATUS_CANCELED  = 'CANCELED';   // 4

	private const LAST_FREE_SEATS_THRESHOLD_DAYS = 7;

	private const DATA_RETENTION = 30;

	/** @var Context */
	protected $database;

	/** @var Statuses */
	protected $trainingStatuses;

	/** @var ITranslator */
	protected $translator;

	/** @var array<string, integer> */
	private $statusIds = array();

	/** @var array<integer, array<string, ArrayHash>> */
	private $upcomingDates = array();


	public function __construct(Context $context, Statuses $trainingStatuses, ITranslator $translator)
	{
		$this->database = $context;
		$this->trainingStatuses = $trainingStatuses;
		$this->translator = $translator;
	}


	public function get(int $dateId): ?Row
	{
		$result = $this->database->fetch(
			'SELECT
				d.id_date AS dateId,
				t.id_training AS trainingId,
				a.action,
				t.name,
				t.price,
				t.student_discount AS studentDiscount,
				d.start,
				d.end,
				d.label AS labelJson,
				d.public,
				s.status,
				v.id_venue AS venueId,
				v.href AS venueHref,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.city AS venueCity,
				c.id_cooperation AS cooperationId,
				d.note
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_date_status s ON d.key_status = s.id_status
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions a ON ta.key_url_action = a.id_url_action
				JOIN languages l ON a.key_language = l.id_language
				LEFT JOIN training_cooperations c ON d.key_cooperation = c.id_cooperation
			WHERE
				d.id_date = ?
				AND l.language = ?',
			$dateId,
			$this->translator->getDefaultLocale()
		);

		if ($result) {
			$result->name = $this->translator->translate($result->name);
		}
		return $result;
	}


	/**
	 * @return Row[]
	 */
	public function getWithUnpaid(): array
	{
		$result = $this->database->fetchAll(
			'SELECT
				d.id_date AS dateId,
				a.action,
				t.name,
				d.start,
				d.end,
				d.public,
				s.status,
				v.href AS venueHref,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.city AS venueCity,
				d.note
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_date_status s ON d.key_status = s.id_status
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions a ON ta.key_url_action = a.id_url_action
				JOIN languages l ON a.key_language = l.id_language
			WHERE
				EXISTS (
					SELECT
						1
					FROM
						training_applications a
					WHERE
						a.key_date = d.id_date
						AND a.paid IS NULL
						AND a.invoice_id IS NOT NULL
						AND a.key_status NOT IN (?)
				)
				AND l.language = ?
			ORDER BY
				d.start',
			array_keys($this->trainingStatuses->getDiscardedStatuses()),
			$this->translator->getDefaultLocale()
		);

		foreach ($result as $date) {
			$date->name = $this->translator->translate($date->name);
		}
		return $result;
	}


	public function update(
		int $dateId,
		int $trainingId,
		int $venueId,
		string $start,
		string $end,
		string $label,
		int $statusId,
		bool $public,
		int $cooperationId,
		string $note
	): void
	{
		$this->database->query(
			'UPDATE training_dates SET ? WHERE id_date = ?',
			array(
				'key_training' => $trainingId,
				'key_venue' => $venueId,
				'start' => new DateTime($start),
				'end' => new DateTime($end),
				'label' => (empty($label) ? null : $label),
				'key_status' => $statusId,
				'public' => $public,
				'key_cooperation' => (empty($cooperationId) ? null : $cooperationId),
				'note' => (empty($note) ? null : $note),
			),
			$dateId
		);
	}


	public function add(
		int $trainingId,
		int $venueId,
		string $start,
		string $end,
		string $label,
		int $statusId,
		bool $public,
		int $cooperationId,
		string $note
	): int
	{
		$this->database->query(
			'INSERT INTO training_dates',
			array(
				'key_training' => $trainingId,
				'key_venue' => $venueId,
				'start' => new DateTime($start),
				'end' => new DateTime($end),
				'label' => (empty($label) ? null : $label),
				'key_status' => $statusId,
				'public' => $public,
				'key_cooperation' => (empty($cooperationId) ? null : $cooperationId),
				'note' => (empty($note) ? null : $note),
			)
		);
		return (int)$this->database->getInsertId();
	}


	/**
	 * @return Row[]
	 */
	public function getStatuses(): array
	{
		$result = $this->database->fetchAll(
			'SELECT
				s.id_status AS id,
				s.status
			FROM training_date_status s
			ORDER BY
				s.id_status'
		);
		return $result;
	}


	public function getStatusId(string $status): int
	{
		if (!isset($this->statusIds[$status])) {
			$this->statusIds[$status] = $this->database->fetchField(
				'SELECT id_status FROM training_date_status WHERE status = ?',
				$status
			);
		}
		return $this->statusIds[$status];
	}


	/**
	 * @return array<string, ArrayHash>
	 */
	public function getPublicUpcoming(): array
	{
		return $this->getUpcoming(false);
	}


	/**
	 * @return integer[]
	 */
	public function getPublicUpcomingIds(): array
	{
		$upcomingIds = array();
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
	 * @param boolean $includeNonPublic
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
					v.id_venue AS venueId,
					v.name AS venueName,
					v.city as venueCity,
					d.note
				FROM training_dates d
					JOIN trainings t ON d.key_training = t.id_training
					JOIN training_url_actions ta ON t.id_training = ta.key_training
					JOIN url_actions a ON ta.key_url_action = a.id_url_action
					JOIN languages l ON a.key_language = l.id_language
					JOIN training_date_status s ON d.key_status = s.id_status
					JOIN training_venues v ON d.key_venue = v.id_venue
					JOIN (
						SELECT
							t2.id_training,
							d2.key_venue,
							MIN(d2.start) AS start
						FROM
							trainings t2
							JOIN training_dates d2 ON t2.id_training = d2.key_training
							JOIN training_date_status s2 ON d2.key_status = s2.id_status
						WHERE
							(d2.public != ? OR TRUE = ?)
							AND d2.end > NOW()
							AND s2.status IN (?, ?)
						GROUP BY
							t2.id_training, d2.key_venue
					) u ON t.id_training = u.id_training AND v.id_venue = u.key_venue AND d.start = u.start
				WHERE
					t.key_successor IS NULL
					AND t.key_discontinued IS NULL
					AND l.language = ?
				ORDER BY
					d.start";

			$upcoming = array();
			foreach ($this->database->fetchAll($query, $includeNonPublic, $includeNonPublic, Dates::STATUS_TENTATIVE, Dates::STATUS_CONFIRMED, $this->translator->getDefaultLocale()) as $row) {
				$date = array(
					'dateId'        => $row->dateId,
					'tentative'     => ($row->status == Dates::STATUS_TENTATIVE),
					'lastFreeSeats' => $this->lastFreeSeats($row),
					'start'         => $row->start,
					'end'           => $row->end,
					'label'         => ($row->labelJson ? Json::decode($row->labelJson)->{$this->translator->getDefaultLocale()} : null),
					'public'        => $row->public,
					'status'        => $row->status,
					'name'          => $this->translator->translate($row->name),
					'venueId'       => $row->venueId,
					'venueName'     => $row->venueName,
					'venueCity'     => $row->venueCity,
					'note'          => $row->note,
				);
				/** @var string $action */
				$action = $row->action;
				$upcoming[$action] = ArrayHash::from(array(
					'action' => $action,
					'name' => $date['name'],
					'dates' => (isset($upcoming[$action]->dates)
						? $upcoming[$action]->dates = (array)$upcoming[$action]->dates + array($row->dateId => $date)
						: array($row->dateId => $date)
					),
				));
			}
			$this->upcomingDates[(int)$includeNonPublic] = $upcoming;
		}

		return $this->upcomingDates[(int)$includeNonPublic];
	}


	/**
	 * @param string $from
	 * @param string $to
	 * @return Row[]
	 */
	public function getAllTrainingsInterval(string $from, string $to = ''): array
	{
		$result = $this->database->fetchAll(
			'SELECT
				d.id_date AS dateId,
				a.action,
				t.name,
				d.start,
				d.end,
				d.public,
				s.status,
				v.href AS venueHref,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.city AS venueCity,
				d.note
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_date_status s ON d.key_status = s.id_status
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions a ON ta.key_url_action = a.id_url_action
				JOIN languages l ON a.key_language = l.id_language
			WHERE
				d.end BETWEEN ? AND ?
				AND l.language = ?
			ORDER BY
				d.start',
			new DateTime($from),
			new DateTime($to),
			$this->translator->getDefaultLocale()
		);

		foreach ($result as $date) {
			$date->name = $this->translator->translate($date->name);
		}
		return $result;
	}


	/**
	 * @param integer $trainingId
	 * @return Row[]
	 */
	public function getDates(int $trainingId): array
	{
		$result = $this->database->fetchAll(
			"SELECT
				d.id_date AS dateId,
				d.start,
				d.end,
				d.label AS labelJson,
				s.status,
				v.href AS venueHref,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.address AS venueAddress,
				v.city AS venueCity,
				v.description AS venueDescription,
				v.action AS venueAction,
				c.description AS cooperationDescription
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_date_status s ON d.key_status = s.id_status
				LEFT JOIN training_cooperations c ON d.key_cooperation = c.id_cooperation
				JOIN (
					SELECT
						t2.id_training,
						d2.key_venue,
						MIN(d2.start) AS start
					FROM
						trainings t2
						JOIN training_dates d2 ON t2.id_training = d2.key_training
						JOIN training_date_status s2 ON d2.key_status = s2.id_status
					WHERE
						d2.public
						AND t2.id_training = ?
						AND d2.end > NOW()
						AND s2.status IN (?, ?)
					GROUP BY
						t2.id_training, d2.key_venue
				) u ON t.id_training = u.id_training AND v.id_venue = u.key_venue AND d.start = u.start
			ORDER BY
				d.start",
			$trainingId,
			Dates::STATUS_TENTATIVE,
			Dates::STATUS_CONFIRMED
		);
		$dates = array();
		foreach ($result as $row) {
			$row->label = ($row->labelJson ? Json::decode($row->labelJson)->{$this->translator->getDefaultLocale()} : null);
			$row->tentative = ($row->status == Dates::STATUS_TENTATIVE);
			$row->lastFreeSeats = $this->lastFreeSeats($row);
			$dates[$row->dateId] = $row;
		}
		return $dates;
	}


	private function lastFreeSeats(Row $date): bool
	{
		$now = new DateTime();
		return ($date->start->diff($now)->days <= self::LAST_FREE_SEATS_THRESHOLD_DAYS && $date->start > $now && $date->status !== Dates::STATUS_TENTATIVE);
	}


	/**
	 * @param Row[] $dates
	 * @return boolean
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


	public function getDataRetentionDays(): int
	{
		return self::DATA_RETENTION;
	}


	public function getDataRetentionDate(): DateTimeImmutable
	{
		return new DateTimeImmutable("-{$this->getDataRetentionDays()} days");
	}

}
