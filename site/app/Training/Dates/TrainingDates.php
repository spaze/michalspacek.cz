<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use Contributte\Translation\Translator;
use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\DateTime\DateTimeFormatter;
use MichalSpacekCz\Training\Exceptions\TrainingDateDoesNotExistException;
use MichalSpacekCz\Training\Exceptions\TrainingDateNotRemoteNoVenueException;
use MichalSpacekCz\Training\Statuses\Statuses;
use Nette\Database\Explorer;

class TrainingDates
{

	private const int DATA_RETENTION = 30;

	/** @var list<TrainingDate>|null */
	private ?array $pastWithPersonalData = null;


	public function __construct(
		private readonly Explorer $database,
		private readonly Statuses $trainingStatuses,
		private readonly DateTimeFormatter $dateTimeFormatter,
		private readonly Translator $translator,
		private readonly TrainingDateFactory $trainingDateFactory,
	) {
	}


	/**
	 * @throws TrainingDateDoesNotExistException
	 */
	public function get(int $dateId): TrainingDate
	{
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
				d.remote,
				v.id_venue AS venueId,
				v.action AS venueAction,
				d.remote_url AS remoteUrl,
				d.remote_notes AS remoteNotes,
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
			$this->translator->getDefaultLocale(),
		);

		if (!$result) {
			throw new TrainingDateDoesNotExistException($dateId);
		}
		return $this->trainingDateFactory->get($result);
	}


	/**
	 * @return list<TrainingDate>
	 */
	public function getWithUnpaid(): array
	{
		$result = $this->database->fetchAll(
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
				LEFT JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_date_status s ON d.key_status = s.id_status
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions a ON ta.key_url_action = a.id_url_action
				JOIN languages l ON a.key_language = l.id_language
				LEFT JOIN training_cooperations c ON d.key_cooperation = c.id_cooperation
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
			$this->translator->getDefaultLocale(),
		);

		$dates = [];
		foreach ($result as $row) {
			$dates[] = $this->trainingDateFactory->get($row);
		}
		return $dates;
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
		string $feedbackHref,
	): void {
		$this->database->query(
			'UPDATE training_dates SET ? WHERE id_date = ?',
			[
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
			],
			$dateId,
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
		string $feedbackHref,
	): int {
		$this->database->query(
			'INSERT INTO training_dates',
			[
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
			],
		);
		return (int)$this->database->getInsertId();
	}


	/**
	 * @return list<TrainingDate>
	 */
	public function getAllTrainings(): array
	{
		$result = $this->database->fetchAll(
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
				NULL AS cooperationId,
				NULL AS cooperationDescription,
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
			WHERE
				l.language = ?
			ORDER BY
				d.start DESC',
			$this->translator->getDefaultLocale(),
		);

		$dates = [];
		foreach ($result as $row) {
			$dates[] = $this->trainingDateFactory->get($row);
		}
		return $dates;
	}


	/**
	 * @return list<TrainingDate>
	 */
	public function getAllTrainingsInterval(string $from, string $to = ''): array
	{
		$result = $this->database->fetchAll(
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
				NULL AS cooperationId,
				NULL AS cooperationDescription,
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
			WHERE
				d.end BETWEEN ? AND ?
				AND l.language = ?
			ORDER BY
				d.start',
			new DateTime($from),
			new DateTime($to),
			$this->translator->getDefaultLocale(),
		);

		$dates = [];
		foreach ($result as $row) {
			$dates[] = $this->trainingDateFactory->get($row);
		}
		return $dates;
	}


	/**
	 * @return list<TrainingDate>
	 */
	public function getPastWithPersonalData(): array
	{
		if ($this->pastWithPersonalData !== null) {
			return $this->pastWithPersonalData;
		}

		$result = $this->database->fetchAll(
			'SELECT DISTINCT
				d.id_date AS dateId,
				t.id_training AS trainingId,
				ua.action,
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
				d.remote,
				d.remote_url AS remoteUrl,
				d.remote_notes AS remoteNotes,
				tv.id_venue AS venueId,
				tv.action AS venueAction,
				tv.href AS venueHref,
				tv.name AS venueName,
				tv.name_extended AS venueNameExtended,
				tv.address AS venueAddress,
				tv.city AS venueCity,
				tv.description AS venueDescription,
				NULL AS cooperationId,
				NULL AS cooperationDescription,
				d.video_href AS videoHref,
				d.feedback_href AS feedbackHref,
				d.note
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_applications ta ON d.id_date = ta.key_date
				LEFT JOIN training_venues tv ON d.key_venue = tv.id_venue
				JOIN training_date_status s ON d.key_status = s.id_status
				JOIN training_url_actions tua ON t.id_training = tua.key_training
				JOIN url_actions ua ON tua.key_url_action = ua.id_url_action
				JOIN languages l ON ua.key_language = l.id_language
			WHERE
				l.language = ?
				AND d.end < ?
				AND (
					ta.name IS NOT NULL OR
					ta.email IS NOT NULL OR
					ta.company IS NOT NULL OR
					ta.street IS NOT NULL OR
					ta.city IS NOT NULL OR
					ta.zip IS NOT NULL OR
					ta.country IS NOT NULL OR
					ta.company_id IS NOT NULL OR
					ta.company_tax_id IS NOT NULL OR
					ta.note IS NOT NULL
				)
			ORDER BY
				d.start DESC',
			$this->translator->getDefaultLocale(),
			$this->getDataRetentionDate(),
		);

		$dates = [];
		foreach ($result as $row) {
			$date = $this->trainingDateFactory->get($row);
			$dates[] = $date;
		}
		return $this->pastWithPersonalData = $dates;
	}


	/**
	 * @param int $trainingId
	 * @return array<int, TrainingDate> id => date
	 */
	public function getDates(int $trainingId): array
	{
		$result = $this->database->fetchAll(
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
				LEFT JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_date_status s ON d.key_status = s.id_status
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions a ON ta.key_url_action = a.id_url_action
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
				d.start',
			$trainingId,
			TrainingDateStatus::Tentative->value,
			TrainingDateStatus::Confirmed->value,
		);

		$dates = [];
		foreach ($result as $row) {
			$date = $this->trainingDateFactory->get($row);
			$dates[$date->getId()] = $date;
		}
		return $dates;
	}


	public function getDataRetentionDays(): int
	{
		return self::DATA_RETENTION;
	}


	public function getDataRetentionDate(): DateTimeImmutable
	{
		return new DateTimeImmutable("-{$this->getDataRetentionDays()} days");
	}


	/**
	 * @throws TrainingDateNotRemoteNoVenueException
	 */
	public function formatDateVenueForAdmin(TrainingDate $date): string
	{
		$isRemote = $date->isRemote();
		$venueCity = $date->getVenueCity();
		if (!$isRemote && $venueCity === null) {
			throw new TrainingDateNotRemoteNoVenueException($date->getId());
		}
		return sprintf(
			'%s, %s',
			$this->dateTimeFormatter->localeIntervalDay($date->getStart(), $date->getEnd()),
			$isRemote ? $this->translator->translate('messages.label.remote') : $venueCity,
		);
	}


	/**
	 * @throws TrainingDateNotRemoteNoVenueException
	 */
	public function formatDateVenueForUser(TrainingDate $date): string
	{
		$interval = $date->isTentative()
			? $this->dateTimeFormatter->localeIntervalMonth($date->getStart(), $date->getEnd())
			: $this->dateTimeFormatter->localeIntervalDay($date->getStart(), $date->getEnd());
		$isRemote = $date->isRemote();
		$venueCity = $date->getVenueCity();
		if (!$isRemote && $venueCity === null) {
			throw new TrainingDateNotRemoteNoVenueException($date->getId());
		}
		return sprintf(
			'%s, %s%s',
			$interval,
			$isRemote ? $this->translator->translate('messages.label.remote') : $venueCity,
			$date->isTentative() ? ' (' . $this->translator->translate('messages.label.tentativedate') . ')' : '',
		);
	}

}
