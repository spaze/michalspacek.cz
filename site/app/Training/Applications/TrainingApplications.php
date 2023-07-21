<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use Contributte\Translation\Translator;
use DateTime;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Exceptions\TrainingApplicationDoesNotExistException;
use MichalSpacekCz\Training\Statuses;
use Nette\Database\Explorer;
use ParagonIE\Halite\Alerts\HaliteAlert;
use SodiumException;

class TrainingApplications
{

	/** @var array<int, list<TrainingApplication>> */
	private array $byDate = [];


	public function __construct(
		private readonly Explorer $database,
		private readonly Statuses $trainingStatuses,
		private readonly TrainingApplicationFactory $trainingApplicationFactory,
		private readonly Translator $translator,
	) {
	}


	/**
	 * @param string $status
	 * @return list<TrainingApplication>
	 * @throws SodiumException
	 * @throws HaliteAlert
	 */
	public function getByStatus(string $status): array
	{
		$result = $this->database->fetchAll(
			'SELECT
				a.id_application AS id,
				a.name,
				a.email,
				a.familiar,
				a.company,
				a.street,
				a.city,
				a.zip,
				a.country,
				a.company_id AS companyId,
				a.company_tax_id AS companyTaxId,
				a.note,
				s.status,
				a.status_time AS statusTime,
				d.id_date AS dateId,
				t.id_training AS trainingId,
				ua.action AS trainingAction,
				t.name AS trainingName,
				d.start AS trainingStart,
				d.end AS trainingEnd,
				d.public AS publicDate,
				d.remote,
				d.remote_url AS remoteUrl,
				d.remote_notes AS remoteNotes,
				d.video_href AS videoHref,
				d.feedback_href AS feedbackHref,
				v.action AS venueAction,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.address AS venueAddress,
				v.city AS venueCity,
				a.price,
				a.vat_rate AS vatRate,
				a.price_vat AS priceVat,
				a.discount,
				a.invoice_id AS invoiceId,
				a.paid,
				a.access_token AS accessToken,
				sr.alias AS sourceAlias,
				sr.name AS sourceName
			FROM
				training_applications a
				LEFT JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON (d.key_training = t.id_training OR a.key_training = t.id_training)
				LEFT JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_application_status s ON a.key_status = s.id_status
				JOIN training_application_sources sr ON a.key_source = sr.id_source
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions ua ON ta.key_url_action = ua.id_url_action
				JOIN languages l ON ua.key_language = l.id_language
			WHERE
				s.status = ?
				AND l.language = ?
			ORDER BY
				d.start, a.status_time',
			$status,
			$this->translator->getDefaultLocale(),
		);

		$applications = [];
		foreach ($result as $row) {
			$applications[] = $this->trainingApplicationFactory->createFromDatabaseRow($row);
		}
		return $applications;
	}


	/**
	 * @return list<TrainingApplication>
	 * @throws SodiumException
	 * @throws HaliteAlert
	 */
	public function getByDate(int $dateId): array
	{
		if (!isset($this->byDate[$dateId])) {
			$result = $this->database->fetchAll(
				'SELECT
					a.id_application AS id,
					a.name,
					a.email,
					a.familiar,
					a.company,
					a.street,
					a.city,
					a.zip,
					a.country,
					a.company_id AS companyId,
					a.company_tax_id AS companyTaxId,
					a.note,
					s.status,
					a.status_time AS statusTime,
					d.id_date AS dateId,
					t.id_training AS trainingId,
					ua.action AS trainingAction,
					t.name AS trainingName,
					d.start AS trainingStart,
					d.end AS trainingEnd,
					d.public AS publicDate,
					d.remote,
					d.remote_url AS remoteUrl,
					d.remote_notes AS remoteNotes,
					d.video_href AS videoHref,
					d.feedback_href AS feedbackHref,
					v.action AS venueAction,
					v.name AS venueName,
					v.name_extended AS venueNameExtended,
					v.address AS venueAddress,
					v.city AS venueCity,
					a.price,
					a.vat_rate AS vatRate,
					a.price_vat AS priceVat,
					a.discount,
					a.invoice_id AS invoiceId,
					a.paid,
					a.access_token AS accessToken,
					sr.alias AS sourceAlias,
					sr.name AS sourceName
				FROM
					training_applications a
					LEFT JOIN training_dates d ON a.key_date = d.id_date
					JOIN trainings t ON (d.key_training = t.id_training OR a.key_training = t.id_training)
					LEFT JOIN training_venues v ON d.key_venue = v.id_venue
					JOIN training_application_status s ON a.key_status = s.id_status
					JOIN training_application_sources sr ON a.key_source = sr.id_source
					JOIN training_url_actions ta ON t.id_training = ta.key_training
					JOIN url_actions ua ON ta.key_url_action = ua.id_url_action
					JOIN languages l ON ua.key_language = l.id_language
				WHERE
					key_date = ?
					AND s.status != ?
					AND l.language = ?',
				$dateId,
				Statuses::STATUS_SPAM,
				$this->translator->getDefaultLocale(),
			);
			$applications = [];
			foreach ($result as $row) {
				$applications[] = $this->trainingApplicationFactory->createFromDatabaseRow($row);
			}
			$this->byDate[$dateId] = $applications;
		}
		return $this->byDate[$dateId];
	}


	/**
	 * @return list<TrainingApplication>
	 * @throws HaliteAlert
	 * @throws SodiumException
	 */
	public function getValidByDate(int $dateId): array
	{
		$discardedStatuses = $this->trainingStatuses->getDiscardedStatuses();
		return array_values(array_filter($this->getByDate($dateId), function (TrainingApplication $value) use ($discardedStatuses): bool {
			return !in_array($value->getStatus(), $discardedStatuses, true);
		}));
	}


	/**
	 * @return list<TrainingApplication>
	 * @throws HaliteAlert
	 * @throws SodiumException
	 */
	public function getValidUnpaidByDate(int $dateId): array
	{
		return array_values(array_filter($this->getValidByDate($dateId), function (TrainingApplication $value): bool {
			return $value->getInvoiceId() && !$value->getPaid();
		}));
	}


	public function getValidUnpaidCount(): int
	{
		$count = $this->database->fetchField(
			'SELECT
				COUNT(1)
			FROM
				training_applications
			WHERE
				key_status NOT IN(?)
				AND invoice_id IS NOT NULL
				AND paid IS NULL',
			array_keys($this->trainingStatuses->getDiscardedStatuses()),
		);
		if (!is_int($count)) {
			throw new ShouldNotHappenException(sprintf("Count is a %s not an integer", get_debug_type($count)));
		}
		return $count;
	}


	/**
	 * Get canceled but already paid applications by date id.
	 *
	 * @return list<TrainingApplication>
	 * @throws HaliteAlert
	 * @throws SodiumException
	 */
	public function getCanceledPaidByDate(int $dateId): array
	{
		$canceledStatus = $this->trainingStatuses->getCanceledStatus();
		return array_values(array_filter($this->getByDate($dateId), function (TrainingApplication $value) use ($canceledStatus): bool {
			return ($value->getPaid() && in_array($value->getStatus(), $canceledStatus));
		}));
	}


	/**
	 * @throws TrainingApplicationDoesNotExistException
	 * @throws HaliteAlert
	 * @throws SodiumException
	 */
	public function getApplicationById(int $id): TrainingApplication
	{
		$result = $this->database->fetch(
			'SELECT
				a.id_application AS id,
				a.name,
				a.email,
				a.familiar,
				a.company,
				a.street,
				a.city,
				a.zip,
				a.country,
				a.company_id AS companyId,
				a.company_tax_id AS companyTaxId,
				a.note,
				s.status,
				a.status_time AS statusTime,
				d.id_date AS dateId,
				t.id_training AS trainingId,
				ua.action AS trainingAction,
				t.name AS trainingName,
				d.start AS trainingStart,
				d.end AS trainingEnd,
				d.public AS publicDate,
				d.remote,
				d.remote_url AS remoteUrl,
				d.remote_notes AS remoteNotes,
				d.video_href AS videoHref,
				d.feedback_href AS feedbackHref,
				v.action AS venueAction,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.address AS venueAddress,
				v.city AS venueCity,
				a.price,
				a.vat_rate AS vatRate,
				a.price_vat AS priceVat,
				a.discount,
				a.invoice_id AS invoiceId,
				a.paid,
				a.access_token AS accessToken,
				sr.alias AS sourceAlias,
				sr.name AS sourceName
			FROM
				training_applications a
				LEFT JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON (d.key_training = t.id_training OR a.key_training = t.id_training)
				LEFT JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_application_status s ON a.key_status = s.id_status
				JOIN training_application_sources sr ON a.key_source = sr.id_source
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions ua ON ta.key_url_action = ua.id_url_action
				JOIN languages l ON ua.key_language = l.id_language
			WHERE
				a.id_application = ?
				AND l.language = ?',
			$id,
			$this->translator->getDefaultLocale(),
		);

		if (!$result) {
			throw new TrainingApplicationDoesNotExistException($id);
		}
		return $this->trainingApplicationFactory->createFromDatabaseRow($result);
	}


	/**
	 * @param string $token
	 * @return TrainingApplication|null
	 * @throws HaliteAlert
	 * @throws SodiumException
	 */
	public function getApplicationByToken(string $token): ?TrainingApplication
	{
		$result = $this->database->fetch(
			'SELECT
				a.id_application AS id,
				a.name,
				a.email,
				a.familiar,
				a.company,
				a.street,
				a.city,
				a.zip,
				a.country,
				a.company_id AS companyId,
				a.company_tax_id AS companyTaxId,
				a.note,
				s.status,
				a.status_time AS statusTime,
				d.id_date AS dateId,
				t.id_training AS trainingId,
				ua.action AS trainingAction,
				t.name AS trainingName,
				d.start AS trainingStart,
				d.end AS trainingEnd,
				d.public AS publicDate,
				d.remote,
				d.remote_url AS remoteUrl,
				d.remote_notes AS remoteNotes,
				d.video_href AS videoHref,
				d.feedback_href AS feedbackHref,
				v.action AS venueAction,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.address AS venueAddress,
				v.city AS venueCity,
				a.price,
				a.vat_rate AS vatRate,
				a.price_vat AS priceVat,
				a.discount,
				a.invoice_id AS invoiceId,
				a.paid,
				a.access_token AS accessToken,
				sr.alias AS sourceAlias,
				sr.name AS sourceName
			FROM
				training_applications a
				LEFT JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON (d.key_training = t.id_training OR a.key_training = t.id_training)
				LEFT JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_application_status s ON a.key_status = s.id_status
				JOIN training_application_sources sr ON a.key_source = sr.id_source
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions ua ON ta.key_url_action = ua.id_url_action
				JOIN languages l ON ua.key_language = l.id_language
			WHERE
				a.access_token = ?
				AND l.language = ?',
			$token,
			$this->translator->getDefaultLocale(),
		);
		return $result ? $this->trainingApplicationFactory->createFromDatabaseRow($result) : null;
	}


	public function setPaidDate(string $invoiceId, string $paid): ?int
	{
		$paidDate = ($paid ? new DateTime($paid) : null);
		$timeZone = $paidDate?->getTimezone()->getName();
		$result = $this->database->query(
			'UPDATE training_applications SET ? WHERE invoice_id = ?',
			[
				'paid' => $paidDate,
				'paid_timezone' => $timeZone,
			],
			(int)$invoiceId,
		);
		return $result->getRowCount();
	}


	public function setAccessTokenUsed(TrainingApplication $application): void
	{
		if (in_array($application->getStatus(), $this->trainingStatuses->getParentStatuses(Statuses::STATUS_ACCESS_TOKEN_USED), true)) {
			$this->trainingStatuses->updateStatus($application->getId(), Statuses::STATUS_ACCESS_TOKEN_USED);
		}
	}


	public function setFamiliar(int $applicationId): void
	{
		$this->database->query('UPDATE training_applications SET familiar = TRUE WHERE id_application = ?', $applicationId);
	}

}
