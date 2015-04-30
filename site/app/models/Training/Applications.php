<?php
namespace MichalSpacekCz\Training;

use Nette\Application\UI\Form;

/**
 * Training applications model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Applications
{

	const DEFAULT_SOURCE  = 'michal-spacek';

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var Dates */
	protected $trainingDates;

	/** @var Statuses */
	protected $trainingStatuses;

	/** @var \MichalSpacekCz\Encryption\Email */
	protected $emailEncryption;

	/** @var \MichalSpacekCz\Vat */
	protected $vat;

	protected $emailFrom;


	public function __construct(
		\Nette\Database\Context $context,
		Dates $trainingDates,
		Statuses $trainingStatuses,
		\MichalSpacekCz\Encryption\Email $emailEncryption,
		\MichalSpacekCz\Vat $vat
	)
	{
		$this->database = $context;
		$this->trainingDates = $trainingDates;
		$this->trainingStatuses = $trainingStatuses;
		$this->emailEncryption = $emailEncryption;
		$this->vat = $vat;
	}


	public function getByStatus($status)
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
				t.name AS trainingName,
				t.action AS trainingAction,
				d.start AS trainingStart,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.address AS venueAddress,
				v.city AS venueCity,
				v.action AS venueAction,
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
				JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_application_status s ON a.key_status = s.id_status
			WHERE
				s.status = ?',
			$status
		);

		if ($result) {
			foreach ($result as $row) {
				$row->email = $this->emailEncryption->decrypt($row->email);
			}
		}

		return $result;
	}


	public function getByDate($dateId)
	{
		$result = $this->database->fetchAll(
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
				a.invoice_id AS invoiceId,
				a.paid,
				a.equipment
			FROM
				training_applications a
				JOIN training_application_status s ON a.key_status = s.id_status
			WHERE
				key_date = ?',
			$dateId
		);

		if ($result) {
			foreach ($result as $row) {
				$row->email = $this->emailEncryption->decrypt($row->email);
			}
		}

		return $result;
	}


	public function getValidByDate($dateId)
	{
		$discardedStatuses = $this->trainingStatuses->getDiscardedStatuses();
		return array_filter($this->getByDate($dateId), function($value) use ($discardedStatuses) {
			return !in_array($value->status, $discardedStatuses);
		});
	}


	public function getValidUnpaidByDate($dateId)
	{
		return array_filter($this->getValidByDate($dateId), function($value) {
			return (isset($value->invoiceId) && !isset($value->paid));
		});
	}


	public function getValidUnpaidCount()
	{
		$result = $this->database->fetchField(
			'SELECT
				COUNT(1)
			FROM
				training_applications
			WHERE
				key_status NOT IN(?)
				AND invoice_id IS NOT NULL
				AND paid IS NULL',
			array_keys($this->trainingStatuses->getDiscardedStatuses())
		);
		return $result;
	}


	private function insertData($data)
	{
		$data['access_token'] = $this->generateAccessCode();
		try {
			$this->database->query('INSERT INTO training_applications', $data);
		} catch (\PDOException $e) {
			if ($e->getCode() == '23000') {
				if ($e->errorInfo[1] == '1062') {  // Integrity constraint violation: 1062 Duplicate entry '...' for key 'access_code_UNIQUE'
					// regenerate the access code and try harder this time
					\Tracy\Debugger::log("Regenerating access token, {$data['access_token']} already exists. Full data: " . implode(', ', $data));
					return $this->insertData($data);
				}
			}
			throw $e;
		}
		return $data['access_token'];
	}


	public function addInvitation(\Nette\Database\Row $training, $dateId, $name, $email, $company, $street, $city, $zip, $country, $companyId, $companyTaxId, $note, $equipment)
	{
		return $this->insertApplication(
			$training,
			$dateId,
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
			$equipment,
			Statuses::STATUS_TENTATIVE,
			self::DEFAULT_SOURCE
		);
	}


	public function addApplication(\Nette\Database\Row $training, $dateId, $name, $email, $company, $street, $city, $zip, $country, $companyId, $companyTaxId, $note, $equipment)
	{
		return $this->insertApplication(
			$training,
			$dateId,
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
			$equipment,
			Statuses::STATUS_SIGNED_UP,
			self::DEFAULT_SOURCE
		);
	}


	public function insertApplication(\Nette\Database\Row $training, $dateId, $name, $email, $company, $street, $city, $zip, $country, $companyId, $companyTaxId, $note, $equipment, $status, $source, $date = null)
	{
		if (!in_array($status, $this->trainingStatuses->getInitialStatuses())) {
			throw new \RuntimeException("Invalid initial status {$status}");
		}

		$statusId = $this->trainingStatuses->getStatusId(Statuses::STATUS_CREATED);
		$datetime = new \DateTime($date);

		if (stripos($note, 'student') === false) {
			$price = $training->price;
			$discount = null;
		} else {
			$price = $training->price * (100 - $training->studentDiscount) / 100;
			$discount = $training->studentDiscount;
		}

		$data = array(
			'key_date'             => $dateId,
			'name'                 => $name,
			'email'                => $this->emailEncryption->encrypt($email),
			'company'              => $company,
			'street'               => $street,
			'city'                 => $city,
			'zip'                  => $zip,
			'country'              => $country,
			'company_id'           => $companyId,
			'company_tax_id'       => $companyTaxId,
			'note'                 => $note,
			'equipment'            => $equipment,
			'key_status'           => $statusId,
			'status_time'          => $datetime,
			'status_time_timezone' => $datetime->getTimezone()->getName(),
			'key_source'           => $this->getTrainingApplicationSource($source),
			'price'                => $price,
			'vat_rate'             => $this->vat->getRate(),
			'price_vat'            => $this->vat->addVat($price),
			'discount'             => $discount,
		);
		return $this->trainingStatuses->updateStatusCallback(function () use ($data) {
			$this->insertData($data);
			return $this->database->getInsertId();
		}, $status, $date);
	}


	public function updateApplication($applicationId, $name, $email, $company, $street, $city, $zip, $country, $companyId, $companyTaxId, $note, $equipment)
	{
		$this->trainingStatuses->updateStatusReturnCallback($applicationId, Statuses::STATUS_SIGNED_UP, null, function () use ($applicationId, $name, $email, $company, $street, $city, $zip, $country, $companyId, $companyTaxId, $note, $equipment) {
			$this->database->query(
				'UPDATE training_applications SET ? WHERE id_application = ?',
				array(
					'name'           => $name,
					'email'          => $this->emailEncryption->encrypt($email),
					'company'        => $company,
					'street'         => $street,
					'city'           => $city,
					'zip'            => $zip,
					'country'        => $country,
					'company_id'     => $companyId,
					'company_tax_id' => $companyTaxId,
					'note'           => $note,
					'equipment'      => $equipment,
				),
				$applicationId
			);
		});
		return $applicationId;
	}


	public function updateApplicationData($applicationId, $name, $email, $company, $street, $city, $zip, $country, $companyId, $companyTaxId, $note, $equipment, $price = null, $vatRate = null, $priceVat = null, $discount = null, $invoiceId = null, $paid = null, $familiar = false)
	{
		if ($paid) {
			$paid = new \DateTime($paid);
		}

		$this->database->query(
			'UPDATE training_applications SET ? WHERE id_application = ?',
			array(
				'name'           => $name,
				'email'          => $this->emailEncryption->encrypt($email),
				'company'        => $company,
				'familiar'       => $familiar,
				'street'         => $street,
				'city'           => $city,
				'zip'            => $zip,
				'country'        => $country,
				'company_id'     => $companyId,
				'company_tax_id' => $companyTaxId,
				'note'           => $note,
				'equipment'      => $equipment,
				'price'          => ($price || $discount ? $price : null),
				'vat_rate'       => ($vatRate ?: null),
				'price_vat'      => ($priceVat ?: null),
				'discount'       => ($discount ?: null),
				'invoice_id'     => ($invoiceId ?: null),
				'paid'           => ($paid ?: null),
				'paid_timezone'  => ($paid ? $paid->getTimezone()->getName() : null),
			),
			$applicationId
		);
	}


	public function updateApplicationInvoiceData($applicationId, $invoiceId)
	{
		$this->database->query(
			'UPDATE training_applications SET ? WHERE id_application = ?',
			array(
				'invoice_id' => ($invoiceId ?: null),
			),
			$applicationId
		);
	}


	private function generateAccessCode()
	{
		return \Nette\Utils\Strings::random(mt_rand(32, 48), '0-9a-zA-Z');
	}


	public function getApplicationById($id)
	{
		$result = $this->database->fetch(
			'SELECT
				t.action AS trainingAction,
				d.id_date AS dateId,
				d.start AS trainingStart,
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
				a.equipment
			FROM
				training_applications a
				JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_application_status s ON a.key_status = s.id_status
			WHERE
				id_application = ?',
			$id
		);

		if ($result) {
			$result->email = $this->emailEncryption->decrypt($result->email);
		}

		return $result;
	}


	public function getApplicationByToken($token)
	{
		$result = $this->database->fetch(
			'SELECT
				t.action AS trainingAction,
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
				a.note,
				a.equipment
			FROM
				training_applications a
				JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON d.key_training = t.id_training
			WHERE
				access_token = ?',
			$token
		);

		if ($result) {
			$result->email = $this->emailEncryption->decrypt($result->email);
		}

		return $result;
	}


	public function setPaidDate($invoiceId, $paid)
	{
		if ($paid) {
			$paid = new \DateTime($paid);
		}

		$result = $this->database->query(
			'UPDATE training_applications SET ? WHERE invoice_id = ?',
			array(
				'paid'           => ($paid ?: null),
				'paid_timezone'  => ($paid ? $paid->getTimezone()->getName() : null),
			),
			$invoiceId
		);
		return $result->getRowCount();
	}


	private function getTrainingApplicationSource($source)
	{
		return $this->database->fetchField('SELECT id_source FROM training_application_sources WHERE alias = ?', $source);
	}


	public function getTrainingApplicationSources()
	{
		return $this->database->fetchAll(
			'SELECT
				id_source AS sourceId,
				alias,
				name
			FROM
				training_application_sources'
		);
	}


	public function setAccessTokenUsed(\Nette\Database\Row $application)
	{
		if ($application->status != Statuses::STATUS_ACCESS_TOKEN_USED) {
			$this->trainingStatuses->updateStatus($application->applicationId, Statuses::STATUS_ACCESS_TOKEN_USED);
		}
	}


	public function countEquipment(array $applications)
	{
		$equipment = 0;
		foreach ($applications as $application) {
			if ($application->equipment && (!isset($application->discarded) || !$application->discarded)) {
				$equipment++;
			}
		}
		return $equipment;
	}

}
