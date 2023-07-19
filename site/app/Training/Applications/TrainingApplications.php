<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use Contributte\Translation\Translator;
use DateTime;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Exceptions\TrainingApplicationDoesNotExistException;
use MichalSpacekCz\Training\Price;
use MichalSpacekCz\Training\Statuses;
use MichalSpacekCz\Training\Trainings\Trainings;
use Nette\Database\Explorer;
use Nette\Database\Row;
use ParagonIE\Halite\Alerts\HaliteAlert;
use Spaze\Encryption\Symmetric\StaticKey;

class TrainingApplications
{

	/** @var array<int, list<Row>> */
	private array $byDate = [];


	public function __construct(
		private readonly Explorer $database,
		private readonly Trainings $trainings,
		private readonly Statuses $trainingStatuses,
		private readonly TrainingApplicationSources $trainingApplicationSources,
		private readonly StaticKey $emailEncryption,
		private readonly Translator $translator,
	) {
	}


	/**
	 * @param string $status
	 * @return Row[]
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
				s.status,
				a.status_time AS statusTime,
				d.id_date AS dateId,
				t.id_training AS trainingId,
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
				a.access_token AS accessToken
			FROM
				training_applications a
				JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON d.key_training = t.id_training
				LEFT JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_application_status s ON a.key_status = s.id_status
			WHERE
				s.status = ?
			ORDER BY
				d.start, a.status_time',
			$status,
		);

		if ($result) {
			foreach ($result as $row) {
				if ($row->email) {
					$row->email = $this->emailEncryption->decrypt($row->email);
				}
				$row->training = $this->trainings->getById($row->trainingId);
				$this->addPricesWithCurrency($row);
			}
		}

		return $result;
	}


	/**
	 * @return list<Row>
	 */
	public function getByDate(int $dateId): array
	{
		if (!isset($this->byDate[$dateId])) {
			$this->byDate[$dateId] = array_values($this->database->fetchAll(
				'SELECT
					a.id_application AS id,
					a.name,
					a.email,
					a.company,
					s.status,
					a.status_time AS statusTime,
					a.note,
					a.price,
					a.vat_rate AS vatRate,
					a.price_vat AS priceVat,
					a.discount,
					a.invoice_id AS invoiceId,
					a.paid,
					sr.name AS sourceName
				FROM
					training_applications a
					JOIN training_application_status s ON a.key_status = s.id_status
					JOIN training_application_sources sr ON a.key_source = sr.id_source
				WHERE
					key_date = ?
					AND s.status != ?',
				$dateId,
				Statuses::STATUS_SPAM,
			));
			if ($this->byDate[$dateId]) {
				$discardedStatuses = $this->trainingStatuses->getDiscardedStatuses();
				$allowFilesStatuses = $this->trainingStatuses->getAllowFilesStatuses();
				foreach ($this->byDate[$dateId] as $row) {
					if ($row->email) {
						$row->email = $this->emailEncryption->decrypt($row->email);
					}
					$row->sourceNameInitials = $this->trainingApplicationSources->getSourceNameInitials($row->sourceName);
					$row->discarded = in_array($row->status, $discardedStatuses);
					$row->allowFiles = in_array($row->status, $allowFilesStatuses);
					$this->addPricesWithCurrency($row);
				}
			}
		}
		return $this->byDate[$dateId];
	}


	/**
	 * @return list<Row>
	 */
	public function getValidByDate(int $dateId): array
	{
		$discardedStatuses = $this->trainingStatuses->getDiscardedStatuses();
		return array_values(array_filter($this->getByDate($dateId), function ($value) use ($discardedStatuses) {
			return !in_array($value->status, $discardedStatuses);
		}));
	}


	/**
	 * @return list<Row>
	 */
	public function getValidUnpaidByDate(int $dateId): array
	{
		return array_values(array_filter($this->getValidByDate($dateId), function ($value) {
			return (isset($value->invoiceId) && !isset($value->paid));
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
	 * @return list<Row>
	 */
	public function getCanceledPaidByDate(int $dateId): array
	{
		$canceledStatus = $this->trainingStatuses->getCanceledStatus();
		return array_values(array_filter($this->getByDate($dateId), function ($value) use ($canceledStatus) {
			return ($value->paid && in_array($value->status, $canceledStatus));
		}));
	}


	/**
	 * @param int $id
	 * @return Row<mixed>
	 * @throws TrainingApplicationDoesNotExistException
	 * @throws HaliteAlert
	 */
	public function getApplicationById(int $id): Row
	{
		/** @var Row<mixed>|null $result */
		$result = $this->database->fetch(
			'SELECT
				ua.action AS trainingAction,
				d.id_date AS dateId,
				d.remote,
				d.start AS trainingStart,
				d.end AS trainingEnd,
				a.id_application AS applicationId,
				s.status,
				a.status_time AS statusTime,
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

		$result->attended = in_array($result->status, $this->trainingStatuses->getAttendedStatuses(), true);
		if ($result->email) {
			$result->email = $this->emailEncryption->decrypt($result->email);
		}
		return $result;
	}


	/**
	 * @param string $token
	 * @return Row<mixed>|null
	 */
	public function getApplicationByToken(string $token): ?Row
	{
		/** @var Row<mixed>|null $result */
		$result = $this->database->fetch(
			'SELECT
				ua.action AS trainingAction,
				d.id_date AS dateId,
				a.id_application AS applicationId,
				a.name,
				a.email,
				a.company,
				a.street,
				a.city,
				a.zip,
				a.country,
				a.company_id AS companyId,
				a.company_tax_id AS companyTaxId,
				a.note
			FROM
				training_applications a
				JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions ua ON ta.key_url_action = ua.id_url_action
				JOIN languages l ON ua.key_language = l.id_language
			WHERE
				a.access_token = ?
				AND l.language = ?',
			$token,
			$this->translator->getDefaultLocale(),
		);

		if ($result && $result->email) {
			$result->email = $this->emailEncryption->decrypt($result->email);
		}

		return $result;
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


	/**
	 * @param Row<mixed> $application
	 */
	public function setAccessTokenUsed(Row $application): void
	{
		if (in_array($application->status, $this->trainingStatuses->getParentStatuses(Statuses::STATUS_ACCESS_TOKEN_USED), true)) {
			$this->trainingStatuses->updateStatus($application->applicationId, Statuses::STATUS_ACCESS_TOKEN_USED);
		}
	}


	public function setFamiliar(int $applicationId): void
	{
		$this->database->query('UPDATE training_applications SET familiar = TRUE WHERE id_application = ?', $applicationId);
	}


	/**
	 * @param Row<mixed> $row
	 */
	private function addPricesWithCurrency(Row $row): void
	{
		$price = new Price($row->price, $row->discount, $row->vatRate, $row->priceVat);
		$row->priceWithCurrency = $price->getPriceWithCurrency();
		$row->priceVatWithCurrency = $price->getPriceVatWithCurrency();
	}

}
