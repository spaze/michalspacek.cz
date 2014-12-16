<?php
namespace MichalSpacekCz\Training;

use \Nette\Application\UI\Form;

/**
 * Training applications model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Applications
{

	const DEFAULT_SOURCE  = 'michal-spacek';

	/** @var \Nette\Database\Connection */
	protected $database;

	/** @var \MichalSpacekCz\Notifier\Vrana */
	protected $vranaNotifier;

	/** @var \MichalSpacekCz\Training\Dates */
	protected $trainingDates;

	/** @var \MichalSpacekCz\Training\Statuses */
	protected $trainingStatuses;

	/** @var \MichalSpacekCz\Encryption\Email */
	protected $emailEncryption;

	protected $emailFrom;

	private $dataRules = array(
		'name'         => array(Form::MIN_LENGTH => 3, Form::MAX_LENGTH => 200),
		'email'        => array(Form::MAX_LENGTH => 200),
		'company'      => array(Form::MIN_LENGTH => 3, Form::MAX_LENGTH => 200),
		'street'       => array(Form::MIN_LENGTH => 3, Form::MAX_LENGTH => 200),
		'city'         => array(Form::MIN_LENGTH => 2, Form::MAX_LENGTH => 200),
		'zip'          => array(Form::PATTERN => '([0-9]\s*){5}', Form::MAX_LENGTH => 200),
		'companyId'    => array(Form::MIN_LENGTH => 6, Form::MAX_LENGTH => 200),
		'companyTaxId' => array(Form::MIN_LENGTH => 6, Form::MAX_LENGTH => 200),
		'note'         => array(Form::MAX_LENGTH => 2000),
	);

	private $statusCallbacks = array();


	public function __construct(
		\Nette\Database\Connection $connection,
		\MichalSpacekCz\Notifier\Vrana $vranaNotifier,
		Dates $trainingDates,
		Statuses $trainingStatuses,
		\MichalSpacekCz\Encryption\Email $emailEncryption
	)
	{
		$this->database = $connection;
		$this->vranaNotifier = $vranaNotifier;
		$this->trainingDates = $trainingDates;
		$this->trainingStatuses = $trainingStatuses;
		$this->emailEncryption = $emailEncryption;
		$this->statusCallbacks[Statuses::STATUS_NOTIFIED] = array($this, 'notifyCallback');
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
				a.paid
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
			return !isset($value->paid);
		});
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
					\Nette\Diagnostics\Debugger::log("Regenerating access token, {$data['access_token']} already exists. Full data: " . implode(', ', $data));
					return $this->insertData($data);
				}
			}
			throw $e;
		}
		return $data['access_token'];
	}


	public function addInvitation($trainingId, $name, $email, $company, $street, $city, $zip, $companyId, $companyTaxId, $note)
	{
		return $this->insertApplication(
			$trainingId,
			$name,
			$email,
			$company,
			$street,
			$city,
			$zip,
			$companyId,
			$companyTaxId,
			$note,
			Statuses::STATUS_TENTATIVE,
			self::DEFAULT_SOURCE
		);
	}


	public function addApplication($trainingId, $name, $email, $company, $street, $city, $zip, $companyId, $companyTaxId, $note)
	{
		return $this->insertApplication(
			$trainingId,
			$name,
			$email,
			$company,
			$street,
			$city,
			$zip,
			$companyId,
			$companyTaxId,
			$note,
			Statuses::STATUS_SIGNED_UP,
			self::DEFAULT_SOURCE
		);
	}


	public function insertApplication($trainingId, $name, $email, $company, $street, $city, $zip, $companyId, $companyTaxId, $note, $status, $source, $date = null)
	{
		if (!in_array($status, $this->getInitialStatuses())) {
			throw new \RuntimeException("Invalid initial status {$status}");
		}

		$statusId = $this->trainingStatuses->getStatusId(Statuses::STATUS_CREATED);
		$datetime = new \DateTime($date);

		$data = array(
			'key_date'             => $trainingId,
			'name'                 => $name,
			'email'                => $this->emailEncryption->encrypt($email),
			'company'              => $company,
			'street'               => $street,
			'city'                 => $city,
			'zip'                  => $zip,
			'company_id'           => $companyId,
			'company_tax_id'       => $companyTaxId,
			'note'                 => $note,
			'key_status'           => $statusId,
			'status_time'          => $datetime,
			'status_time_timezone' => $datetime->getTimezone()->getName(),
			'key_source'           => $this->getTrainingApplicationSource($source),
		);
		return $this->updateStatusCallback(function () use ($data) {
			$this->insertData($data);
			return $this->database->getInsertId();
		}, $status, $date);
	}


	public function updateApplication($applicationId, $name, $email, $company, $street, $city, $zip, $companyId, $companyTaxId, $note)
	{
		$this->updateStatusReturnCallback($applicationId, Statuses::STATUS_SIGNED_UP, null, function () use ($applicationId, $name, $email, $company, $street, $city, $zip, $companyId, $companyTaxId, $note) {
			$this->updateApplicationData(
				$applicationId,
				$name,
				$email,
				$company,
				$street,
				$city,
				$zip,
				$companyId,
				$companyTaxId,
				$note
			);
		});
		return $applicationId;
	}


	public function updateApplicationData($applicationId, $name, $email, $company, $street, $city, $zip, $companyId, $companyTaxId, $note, $price = null, $vatRate = null, $priceVat = null, $discount = null, $invoiceId = null, $paid = null, $familiar = false)
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
				'company_id'     => $companyId,
				'company_tax_id' => $companyTaxId,
				'note'           => $note,
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


	/**
	 * Needs to be wrapped in transaction, not for public consumption, updateStatus() or updateStatusCallback() instead.
	 */
	private function setStatus($applicationId, $status, $date)
	{
		$statusId = $this->trainingStatuses->getStatusId($status);

		$prevStatus = $this->database->fetch(
			'SELECT
				key_status AS statusId,
				status_time AS statusTime,
				status_time_timezone AS statusTimeTimeZone
			FROM
				training_applications
			WHERE
				id_application = ?',
			$applicationId
		);

		$datetime = new \DateTime($date);
		$this->database->query(
			'UPDATE training_applications SET ? WHERE id_application = ?',
			array(
				'key_status'           => $statusId,
				'status_time'          => $datetime,
				'status_time_timezone' => $datetime->getTimezone()->getName(),
			),
			$applicationId
		);

		$result = $this->database->query(
			'INSERT INTO training_application_status_history',
			array(
				'key_application'      => $applicationId,
				'key_status'           => $prevStatus->statusId,
				'status_time'          => $prevStatus->statusTime,
				'status_time_timezone' => $prevStatus->statusTimeTimeZone,
			)
		);

		if (isset($this->statusCallbacks[$status]) && is_callable($this->statusCallbacks[$status])) {
			call_user_func($this->statusCallbacks[$status], $applicationId);
		}

		return $result;
	}


	public function updateStatus($applicationId, $status, $date = null)
	{
		$this->database->beginTransaction();
		try {
			$this->setStatus($applicationId, $status, $date);
			$this->database->commit();
		} catch (\Exception $e) {
			$this->database->rollBack();
		}
	}


	public function updateStatusCallback(callable $callback, $status, $date)
	{
		$this->database->beginTransaction();
		try {
			$applicationId = $callback();
			$this->setStatus($applicationId, $status, $date);
			$this->database->commit();
		} catch (\Exception $e) {
			$this->database->rollBack();
		}
		return $applicationId;
	}


	public function updateStatusReturnCallback($applicationId, $status, $date, callable $callback)
	{
		$this->database->beginTransaction();
		try {
			$result = $callback();
			$this->setStatus($applicationId, $status, $date);
			$this->database->commit();
		} catch (\Exception $e) {
			$this->database->rollBack();
		}
		return $result;
	}


	public function getApplicationById($id)
	{
		$result = $this->database->fetch(
			'SELECT
				t.action,
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
				a.company_id AS companyId,
				a.company_tax_id AS companyTaxId,
				a.note,
				a.price,
				a.vat_rate AS vatRate,
				a.price_vat AS priceVat,
				a.discount,
				a.invoice_id AS invoiceId,
				a.paid
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
				t.action,
				d.id_date AS dateId,
				a.id_application AS applicationId,
				a.name,
				a.email,
				a.company,
				a.street,
				a.city,
				a.zip,
				a.company_id AS companyId,
				a.company_tax_id AS companyTaxId,
				a.note
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
			$this->updateStatus($application->applicationId, Statuses::STATUS_ACCESS_TOKEN_USED);
		}
	}


	public function getDataRules()
	{
		return $this->dataRules;
	}


	private function notifyCallback($applicationId)
	{
		$application = $this->getApplicationById($applicationId);
		$date = $this->trainingDates->get($application->dateId);
		if ($date->public && !$date->cooperationId) {
			$this->vranaNotifier->addTrainingApplication($application);
		}
	}

}
