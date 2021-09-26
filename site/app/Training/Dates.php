<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use DateTime;
use DateTimeImmutable;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Localization\Translator;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;
use Netxten\Templating\Helpers;

class Dates
{

	public const STATUS_CREATED   = 'CREATED';    // 1
	public const STATUS_TENTATIVE = 'TENTATIVE';  // 2
	public const STATUS_CONFIRMED = 'CONFIRMED';  // 3
	public const STATUS_CANCELED  = 'CANCELED';   // 4

	private const LAST_FREE_SEATS_THRESHOLD_DAYS = 7;

	private const DATA_RETENTION = 30;

	private Explorer $database;

	private Statuses $trainingStatuses;

	private Prices $prices;

	private Helpers $netxtenHelpers;

	private Translator $translator;

	/** @var array<string, int> */
	private array $statusIds = array();

	/** @var array<int, array<string, ArrayHash>> */
	private array $upcomingDates = array();


	public function __construct(Explorer $context, Statuses $trainingStatuses, Prices $prices, Helpers $netxtenHelpers, Translator $translator)
	{
		$this->database = $context;
		$this->trainingStatuses = $trainingStatuses;
		$this->prices = $prices;
		$this->netxtenHelpers = $netxtenHelpers;
		$this->translator = $translator;
	}


	/**
	 * @param int $dateId
	 * @return Row<mixed>|null
	 */
	public function get(int $dateId): ?Row
	{
		/** @var Row<mixed>|null $result */
		$result = $this->database->fetch(
			'SELECT
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
				d.public,
				s.status,
				v.id_venue AS venueId,
				d.remote,
				d.remote_url AS remoteUrl,
				d.remote_notes AS remoteNotes,
				v.href AS venueHref,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.city AS venueCity,
				c.id_cooperation AS cooperationId,
				d.video_href AS videoHref,
				d.feedback_href AS feedbackHref,
				d.note
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				LEFT JOIN training_venues v ON d.key_venue = v.id_venue
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
			$result->price = $result->price ? $this->prices->resolvePriceVat($result->price) : null;
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
				d.label AS labelJson,
				d.public,
				s.status,
				d.remote,
				v.href AS venueHref,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.city AS venueCity,
				d.note
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				LEFT JOIN training_venues v ON d.key_venue = v.id_venue
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
			$date->label = $this->decodeLabel($date->labelJson);
		}
		return $result;
	}


	public function update(
		int $dateId,
		int $trainingId,
		?int $venueId,
		bool $remote,
		string $start,
		string $end,
		string $label,
		int $statusId,
		bool $public,
		int $cooperationId,
		string $note,
		?int $price,
		?int $studentDiscount,
		string $remoteUrl,
		string $remoteNotes,
		string $videoHref,
		string $feedbackHref
	): void {
		$this->database->query(
			'UPDATE training_dates SET ? WHERE id_date = ?',
			array(
				'key_training' => $trainingId,
				'key_venue' => $venueId,
				'remote' => $remote,
				'start' => new DateTime($start),
				'end' => new DateTime($end),
				'label' => (empty($label) ? null : $label),
				'key_status' => $statusId,
				'public' => $public,
				'key_cooperation' => (empty($cooperationId) ? null : $cooperationId),
				'note' => (empty($note) ? null : $note),
				'price' => $price,
				'student_discount' => $studentDiscount,
				'remote_url' => (empty($remoteUrl) ? null : $remoteUrl),
				'remote_notes' => (empty($remoteNotes) ? null : trim($remoteNotes)),
				'video_href' => (empty($videoHref) ? null : $videoHref),
				'feedback_href' => (empty($feedbackHref) ? null : $feedbackHref),
			),
			$dateId
		);
	}


	public function add(
		int $trainingId,
		?int $venueId,
		bool $remote,
		string $start,
		string $end,
		string $label,
		int $statusId,
		bool $public,
		int $cooperationId,
		string $note,
		?int $price,
		?int $studentDiscount,
		string $remoteUrl,
		string $remoteNotes,
		string $videoHref,
		string $feedbackHref
	): int {
		$this->database->query(
			'INSERT INTO training_dates',
			array(
				'key_training' => $trainingId,
				'key_venue' => $venueId,
				'remote' => $remote,
				'start' => new DateTime($start),
				'end' => new DateTime($end),
				'label' => (empty($label) ? null : $label),
				'key_status' => $statusId,
				'public' => $public,
				'key_cooperation' => (empty($cooperationId) ? null : $cooperationId),
				'note' => (empty($note) ? null : $note),
				'price' => $price,
				'student_discount' => $studentDiscount,
				'remote_url' => (empty($remoteUrl) ? null : $remoteUrl),
				'remote_notes' => (empty($remoteNotes) ? null : trim($remoteNotes)),
				'video_href' => (empty($videoHref) ? null : $videoHref),
				'feedback_href' => (empty($feedbackHref) ? null : $feedbackHref),
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
				s.status,
				description
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
	 * @return int[]
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
					v.city as venueCity,
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

			$upcoming = array();
			foreach ($this->database->fetchAll($query, $includeNonPublic, $includeNonPublic, Dates::STATUS_TENTATIVE, Dates::STATUS_CONFIRMED, $this->translator->getDefaultLocale()) as $row) {
				$date = array(
					'dateId'        => $row->dateId,
					'tentative'     => ($row->status == Dates::STATUS_TENTATIVE),
					'lastFreeSeats' => $this->lastFreeSeats($row),
					'start'         => $row->start,
					'end'           => $row->end,
					'label'         => $this->decodeLabel($row->labelJson),
					'public'        => $row->public,
					'status'        => $row->status,
					'name'          => $this->translator->translate($row->name),
					'remote' => (bool)$row->remote,
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
				d.label AS labelJson,
				d.public,
				s.status,
				d.remote,
				v.href AS venueHref,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.city AS venueCity,
				d.note
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				LEFT JOIN training_venues v ON d.key_venue = v.id_venue
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
			$date->label = $this->decodeLabel($date->labelJson);
		}
		return $result;
	}


	/**
	 * @param int $trainingId
	 * @return Row[]
	 */
	public function getDates(int $trainingId): array
	{
		$result = $this->database->fetchAll(
			"SELECT
				d.id_date AS dateId,
				t.id_training AS trainingId,
				COALESCE(d.price, t.price) AS price,
				COALESCE(d.student_discount, t.student_discount) AS studentDiscount,
				d.start,
				d.end,
				d.label AS labelJson,
				s.status,
				d.remote,
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
				LEFT JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_date_status s ON d.key_status = s.id_status
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
						d2.public
						AND t2.id_training = ?
						AND d2.end > NOW()
						AND s2.status IN (?, ?)
				) u ON t.id_training = u.id_training AND (v.id_venue = u.key_venue OR u.key_venue IS NULL) AND d.start = u.start
			ORDER BY
				d.start",
			$trainingId,
			Dates::STATUS_TENTATIVE,
			Dates::STATUS_CONFIRMED
		);
		$dates = array();
		foreach ($result as $row) {
			$row->remote = (bool)$row->remote;
			$row->label = $this->decodeLabel($row->labelJson);
			$row->tentative = ($row->status == Dates::STATUS_TENTATIVE);
			$row->lastFreeSeats = $this->lastFreeSeats($row);
			$row->price = $row->price ? $this->prices->resolvePriceVat($row->price) : null;
			$dates[$row->dateId] = $row;
		}
		return $dates;
	}


	/**
	 * @param Row<mixed> $date
	 * @return bool
	 */
	private function lastFreeSeats(Row $date): bool
	{
		$now = new DateTime();
		return ($date->start->diff($now)->days <= self::LAST_FREE_SEATS_THRESHOLD_DAYS && $date->start > $now && $date->status !== Dates::STATUS_TENTATIVE);
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


	public function getDataRetentionDays(): int
	{
		return self::DATA_RETENTION;
	}


	public function getDataRetentionDate(): DateTimeImmutable
	{
		return new DateTimeImmutable("-{$this->getDataRetentionDays()} days");
	}


	public function decodeLabel(?string $json): ?string
	{
		return ($json ? Json::decode($json)->{$this->translator->getDefaultLocale()} : null);
	}


	public function formatDateVenueForAdmin(ArrayHash $date): string
	{
		return sprintf(
			'%s, %s',
			$this->netxtenHelpers->localeIntervalDay($date->start ?? $date->trainingStart, $date->end ?? $date->trainingEnd),
			$date->remote ? $this->translator->translate('messages.label.remote') : $date->venueCity
		);
	}


	public function formatDateVenueForUser(ArrayHash $date): string
	{
		$start = $date->start ?? $date->trainingStart;
		$end = $date->end ?? $date->trainingEnd;
		return sprintf(
			'%s, %s%s',
			$date->tentative ? $this->netxtenHelpers->localeIntervalMonth($start, $end) : $this->netxtenHelpers->localeIntervalDay($start, $end),
			$date->remote ? $this->translator->translate('messages.label.remote') : $date->venueCity,
			$date->tentative ? ' (' . $this->translator->translate('messages.label.tentativedate') . ')' : ''
		);
	}

}
