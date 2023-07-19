<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use Contributte\Translation\Translator;
use DateTime;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use MichalSpacekCz\Training\Exceptions\TrainingApplicationDoesNotExistException;
use MichalSpacekCz\Training\Price;
use MichalSpacekCz\Training\Prices;
use MichalSpacekCz\Training\Resolver\Vrana;
use MichalSpacekCz\Training\Statuses;
use MichalSpacekCz\Training\Trainings\Trainings;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use ParagonIE\Halite\Alerts\HaliteAlert;
use RuntimeException;
use Spaze\Encryption\Symmetric\StaticKey;
use Tracy\Debugger;

class TrainingApplications
{

	private const SOURCE_MICHAL_SPACEK = 'michal-spacek';
	private const SOURCE_JAKUB_VRANA = 'jakub-vrana';

	/** @var array<int, list<Row>> */
	private array $byDate = [];


	public function __construct(
		private readonly Explorer $database,
		private readonly Trainings $trainings,
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly Statuses $trainingStatuses,
		private readonly StaticKey $emailEncryption,
		private readonly Prices $prices,
		private readonly Vrana $vranaResolver,
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
					$row->sourceNameInitials = $this->getSourceNameInitials($row->sourceName);
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
	 * @param array<string, string|int|float|DateTime|null> $data
	 * @return string Generated access token
	 */
	private function insertData(array $data): string
	{
		$data['access_token'] = $token = $this->generateAccessCode();
		try {
			$this->database->query('INSERT INTO training_applications', $data);
		} catch (UniqueConstraintViolationException) {
			// regenerate the access code and try harder this time
			Debugger::log("Regenerating access token, {$token} already exists");
			return $this->insertData($data);
		}
		return $token;
	}


	public function addInvitation(
		TrainingDate $date,
		string $name,
		string $email,
		string $company,
		string $street,
		string $city,
		string $zip,
		string $country,
		string $companyId,
		string $companyTaxId,
		string $note,
	): int {
		return $this->insertApplication(
			$date->getTrainingId(),
			$date->getId(),
			$name,
			$email,
			$company,
			$street,
			$city,
			$zip,
			$country,
			$companyId,
			$companyTaxId,
			$note,
			$date->getPrice(),
			$date->getStudentDiscount(),
			Statuses::STATUS_TENTATIVE,
			$this->resolveSource($note),
		);
	}


	public function addApplication(
		TrainingDate $date,
		string $name,
		string $email,
		string $company,
		string $street,
		string $city,
		string $zip,
		string $country,
		string $companyId,
		string $companyTaxId,
		string $note,
	): int {
		return $this->insertApplication(
			$date->getTrainingId(),
			$date->getId(),
			$name,
			$email,
			$company,
			$street,
			$city,
			$zip,
			$country,
			$companyId,
			$companyTaxId,
			$note,
			$date->getPrice(),
			$date->getStudentDiscount(),
			Statuses::STATUS_SIGNED_UP,
			$this->resolveSource($note),
		);
	}


	/**
	 * Add preliminary invitation, to a training with no date set.
	 *
	 * @param int $trainingId
	 * @param string $name
	 * @param string $email
	 * @return int application id
	 */
	public function addPreliminaryInvitation(int $trainingId, string $name, string $email): int
	{
		return $this->insertApplication(
			$trainingId,
			null,
			$name,
			$email,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			Statuses::STATUS_TENTATIVE,
			$this->resolveSource(),
		);
	}


	public function insertApplication(
		int $trainingId,
		?int $dateId,
		string $name,
		string $email,
		?string $company,
		?string $street,
		?string $city,
		?string $zip,
		?string $country,
		?string $companyId,
		?string $companyTaxId,
		?string $note,
		?Price $price,
		?int $studentDiscount,
		string $status,
		string $source,
		?string $date = null,
	): int {
		if (!in_array($status, $this->trainingStatuses->getInitialStatuses())) {
			throw new RuntimeException("Invalid initial status {$status}");
		}

		$statusId = $this->trainingStatuses->getStatusId(Statuses::STATUS_CREATED);
		$datetime = new DateTime($date ?? '');

		$customerPrice = $this->prices->resolvePriceDiscountVat($price, $studentDiscount, $status, $note ?? '');

		$timeZone = $datetime->getTimezone()->getName();
		$data = [
			'key_date' => $dateId,
			'name' => $name,
			'email' => $this->emailEncryption->encrypt($email),
			'company' => $company,
			'street' => $street,
			'city' => $city,
			'zip' => $zip,
			'country' => $country,
			'company_id' => $companyId,
			'company_tax_id' => $companyTaxId,
			'note' => $note,
			'key_status' => $statusId,
			'status_time' => $datetime,
			'status_time_timezone' => $timeZone,
			'key_source' => $this->getTrainingApplicationSource($source),
			'price' => $customerPrice->getPrice(),
			'vat_rate' => $customerPrice->getVatRate(),
			'price_vat' => $customerPrice->getPriceVat(),
			'discount' => $customerPrice->getDiscount(),
		];
		if ($dateId === null) {
			$data['key_training'] = $trainingId;
		}
		return $this->trainingStatuses->updateStatusCallbackReturnId(function () use ($data): int {
			$this->insertData($data);
			return (int)$this->database->getInsertId();
		}, $status, $date);
	}


	public function updateApplication(
		TrainingDate $date,
		int $applicationId,
		string $name,
		string $email,
		string $company,
		string $street,
		string $city,
		string $zip,
		string $country,
		string $companyId,
		string $companyTaxId,
		string $note,
	): int {
		$this->trainingStatuses->updateStatusCallback(
			$applicationId,
			Statuses::STATUS_SIGNED_UP,
			null,
			function () use (
				$date,
				$applicationId,
				$name,
				$email,
				$company,
				$street,
				$city,
				$zip,
				$country,
				$companyId,
				$companyTaxId,
				$note
			): void {
				$price = $this->prices->resolvePriceDiscountVat($date->getPrice(), $date->getStudentDiscount(), Statuses::STATUS_SIGNED_UP, $note);
				$this->database->query(
					'UPDATE training_applications SET ? WHERE id_application = ?',
					[
						'name' => $name,
						'email' => $this->emailEncryption->encrypt($email),
						'company' => $company,
						'street' => $street,
						'city' => $city,
						'zip' => $zip,
						'country' => $country,
						'company_id' => $companyId,
						'company_tax_id' => $companyTaxId,
						'note' => $note,
						'price' => $price->getPrice(),
						'vat_rate' => $price->getVatRate(),
						'price_vat' => $price->getPriceVat(),
						'discount' => $price->getDiscount(),
					],
					$applicationId,
				);
			},
		);
		return $applicationId;
	}


	public function updateApplicationData(
		int $applicationId,
		?string $name,
		?string $email,
		?string $company,
		?string $street,
		?string $city,
		?string $zip,
		?string $country,
		?string $companyId,
		?string $companyTaxId,
		?string $note,
		string $source,
		?float $price = null,
		?float $vatRate = null,
		?float $priceVat = null,
		?int $discount = null,
		?string $invoiceId = null,
		string $paid = null,
		bool $familiar = false,
		?int $dateId = null,
	): void {
		$paidDate = ($paid ? new DateTime($paid) : null);
		$timeZone = $paidDate?->getTimezone()->getName();
		$data = [
			'name' => $name,
			'email' => ($email ? $this->emailEncryption->encrypt($email) : null),
			'company' => $company,
			'familiar' => $familiar,
			'street' => $street,
			'city' => $city,
			'zip' => $zip,
			'country' => $country,
			'company_id' => $companyId,
			'company_tax_id' => $companyTaxId,
			'note' => $note,
			'key_source' => $this->getTrainingApplicationSource($source),
			'price' => ($price || $discount ? $price : null),
			'vat_rate' => ($vatRate ?: null),
			'price_vat' => ($priceVat ?: null),
			'discount' => ($discount ?: null),
			'invoice_id' => ((int)$invoiceId ?: null),
			'paid' => $paidDate,
			'paid_timezone' => $timeZone,
		];
		if ($dateId !== null) {
			$data['key_date'] = $dateId;
		}
		$this->database->query('UPDATE training_applications SET ? WHERE id_application = ?', $data, $applicationId);
	}


	public function updateApplicationInvoiceData(int $applicationId, string $invoiceId): void
	{
		$this->database->query(
			'UPDATE training_applications SET ? WHERE id_application = ?',
			[
				'invoice_id' => ((int)$invoiceId ?: null),
			],
			$applicationId,
		);
	}


	private function resolveSource(string $note = null): string
	{
		if ($note && $this->vranaResolver->isTrainingApplicationOwner($note)) {
			$source = self::SOURCE_JAKUB_VRANA;
		} else {
			$source = self::SOURCE_MICHAL_SPACEK;
		}
		return $source;
	}


	private function generateAccessCode(): string
	{
		return Random::generate(14, '0-9a-zA-Z');
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
	 * @return Row[]
	 */
	public function getPreliminary(): array
	{
		$trainings = [];
		$result = $this->database->fetchAll(
			'SELECT
				t.id_training AS idTraining,
				ua.action,
				t.name
			FROM trainings t
				JOIN training_applications a ON a.key_training = t.id_training
				JOIN training_application_status s ON a.key_status = s.id_status
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions ua ON ta.key_url_action = ua.id_url_action
				JOIN languages l ON ua.key_language = l.id_language
			WHERE
				a.key_date IS NULL
				AND s.status != ?
				AND l.language = ?',
			Statuses::STATUS_CANCELED,
			$this->translator->getDefaultLocale(),
		);
		foreach ($result as $row) {
			$row->name = $this->translator->translate($row->name);
			$row->applications = [];
			$trainings[$row->idTraining] = $row;
		}

		$applications = $this->database->fetchAll(
			'SELECT
				a.id_application AS id,
				a.key_training AS idTraining,
				a.name,
				a.email,
				a.company,
				s.status,
				a.status_time AS statusTime,
				a.note,
				a.price,
				a.vat_rate AS vatRate,
				a.price_vat AS priceVat,
				a.invoice_id AS invoiceId,
				a.paid,
				sr.name AS sourceName
			FROM
				training_applications a
				JOIN training_application_status s ON a.key_status = s.id_status
				JOIN training_application_sources sr ON a.key_source = sr.id_source
			WHERE
				a.key_date IS NULL
				AND s.status != ?',
			Statuses::STATUS_CANCELED,
		);

		if ($applications) {
			foreach ($applications as $row) {
				if ($row->email) {
					$row->email = $this->emailEncryption->decrypt($row->email);
				}
				$row->sourceNameInitials = $this->getSourceNameInitials($row->sourceName);
				$trainings[$row->idTraining]->applications[] = $row;
			}
		}

		return $trainings;
	}


	/**
	 * @return int[]
	 */
	public function getPreliminaryCounts(): array
	{
		$upcoming = array_keys($this->upcomingTrainingDates->getPublicUpcoming());

		$total = $dateSet = 0;
		foreach ($this->getPreliminary() as $training) {
			if (in_array($training->action, $upcoming)) {
				$dateSet += count($training->applications);
			}
			$total += count($training->applications);
		}

		return [$total, $dateSet];
	}


	/**
	 * @return Row[]
	 */
	public function getPreliminaryWithDateSet(): array
	{
		$upcoming = array_keys($this->upcomingTrainingDates->getPublicUpcoming());

		$applications = [];
		foreach ($this->getPreliminary() as $training) {
			if (in_array($training->action, $upcoming)) {
				foreach ($training->applications as $application) {
					$application->training = $training;
					$applications[] = $application;
				}
			}
		}
		return $applications;
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


	private function getTrainingApplicationSource(string $source): int
	{
		$id = $this->database->fetchField('SELECT id_source FROM training_application_sources WHERE alias = ?', $source);
		if (!is_int($id)) {
			throw new ShouldNotHappenException(sprintf("Source id for source '%s' is a %s not an integer", $source, get_debug_type($id)));
		}
		return $id;
	}


	/**
	 * @return Row[]
	 */
	public function getTrainingApplicationSources(): array
	{
		return $this->database->fetchAll(
			'SELECT
				id_source AS sourceId,
				alias,
				name
			FROM
				training_application_sources',
		);
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


	/**
	 * Shorten source name.
	 *
	 * Removes Czech private limited company designation, if any, and uses only initials from the original name.
	 * Example:
	 *   Michal Špaček -> MŠ
	 *   Internet Info, s.r.o. -> II
	 *
	 * @param string $name
	 * @return string
	 */
	private function getSourceNameInitials(string $name): string
	{
		$name = Strings::replace($name, '/,? s\.r\.o./', '');
		$matches = Strings::matchAll($name, '/(?<=\s|\b)\pL/u', PREG_PATTERN_ORDER);
		return Strings::upper(implode('', current($matches)));
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
