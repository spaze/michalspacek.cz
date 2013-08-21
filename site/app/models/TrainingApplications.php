<?php
namespace MichalSpacekCz;

use \Nette\Application\UI\Form;

/**
 * Training applications model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingApplications extends BaseModel
{

	const STATUS_CREATED   = 'CREATED';
	const STATUS_TENTATIVE = 'TENTATIVE';
	const STATUS_SIGNED_UP = 'SIGNED_UP';
	const STATUS_ATTENDED = 'ATTENDED';
	const STATUS_MATERIALS_SENT = 'MATERIALS_SENT';
	const STATUS_ACCESS_TOKEN_USED = 'ACCESS_TOKEN_USED';
	const STATUS_IMPORTED = 'IMPORTED';
	const STATUS_NON_PUBLIC_TRAINING = 'NON_PUBLIC_TRAINING';

	const DEFAULT_SOURCE  = 'michal-spacek';

	protected $filesDir;

	protected $emailFrom;

	private $statuses = array();

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


	public function getByStatus($status)
	{
		return $this->database->fetchAll(
			'SELECT
				a.name,
				a.email,
				t.name AS trainingName,
				t.action AS trainingAction,
				a.access_token AS accessToken
			FROM
				training_applications a
				JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_application_status s ON a.key_status = s.id_status
			WHERE
				s.status = ?',
			$status
		);
	}


	public function getByDate($dateId)
	{
		return $this->database->fetchAll(
			'SELECT
				a.id_application AS id,
				a.name,
				a.email,
				a.company,
				s.status,
				a.status_time AS statusTime
			FROM
				training_applications a
				JOIN training_application_status s ON a.key_status = s.id_status
			WHERE
				key_date = ?',
			$dateId
		);
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
			self::STATUS_TENTATIVE,
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
			self::STATUS_SIGNED_UP,
			self::DEFAULT_SOURCE
		);
	}


	public function insertApplication($trainingId, $name, $email, $company, $street, $city, $zip, $companyId, $companyTaxId, $note, $status, $source, $date = null)
	{
		if (!in_array($status, $this->getInitialStatuses())) {
			throw new \RuntimeException("Invalid initial status {$status}");
		}

		$statusId = $this->getStatusId(self::STATUS_CREATED);
		$datetime = new \DateTime($date);

		$this->database->beginTransaction();
		$data = array(
			'key_date'             => $trainingId,
			'name'                 => $name,
			'email'                => $email,
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
		$code = $this->insertData($data);
		$applicationId = $this->database->lastInsertId();
		$this->setStatus($applicationId, $status, $date);
		$this->database->commit();

		return $applicationId;
	}


	public function updateApplication($applicationId, $name, $email, $company, $street, $city, $zip, $companyId, $companyTaxId, $note)
	{
		$this->database->beginTransaction();
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
		$this->setStatus($applicationId, self::STATUS_SIGNED_UP);
		$this->database->commit();
		return $applicationId;
	}


	public function updateApplicationData($applicationId, $name, $email, $company, $street, $city, $zip, $companyId, $companyTaxId, $note, $price = null, $discount = null, $invoiceId = null, $paid = null)
	{
		if ($paid) {
			$paid = new \DateTime($paid);
		}

		$this->database->query(
			'UPDATE training_applications SET ? WHERE id_application = ?',
			array(
				'name'           => $name,
				'email'          => $email,
				'company'        => $company,
				'street'         => $street,
				'city'           => $city,
				'zip'            => $zip,
				'company_id'     => $companyId,
				'company_tax_id' => $companyTaxId,
				'note'           => $note,
				'price'          => ($price ?: null),
				'discount'       => ($discount ?: null),
				'invoice_id'     => ($invoiceId ?: null),
				'paid'           => ($paid ?: null),
				'paid_timezone'  => ($paid ? $paid->getTimezone()->getName() : null),
			),
			$applicationId
		);
	}


	public function getInitialStatuses()
	{
		return $this->getChildrenStatuses(self::STATUS_CREATED);
	}


	public function getAttendedStatuses()
	{
		return array($this->getStatusId(self::STATUS_ATTENDED) => self::STATUS_ATTENDED) + $this->getDescendantStatuses(self::STATUS_ATTENDED);
	}


	public function getChildrenStatuses($parent)
	{
		if (!isset($this->statuses[$parent])) {
			$this->statuses[$parent] = $this->database->fetchPairs(
				'SELECT
					st.id_status,
					st.status
				FROM training_application_status_flow f
					JOIN training_application_status sf ON sf.id_status = f.key_status_from
					JOIN training_application_status st ON st.id_status = f.key_status_to
				WHERE sf.status = ?',
				$parent
			);
		}
		return $this->statuses[$parent];
	}


	private function getDescendantStatuses($parent)
	{
		$statuses = $this->getChildrenStatuses($parent);
		foreach ($statuses as $status) {
			$statuses += $this->getDescendantStatuses($status);
		}
		return $statuses;
	}


	private function generateAccessCode()
	{
		return \Nette\Utils\Strings::random(mt_rand(32, 64), '0-9a-zA-Z');
	}


	public function setStatus($applicationId, $status, $date = null)
	{
		$statusId = $this->getStatusId($status);

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

		return $this->database->query(
			'INSERT INTO training_application_status_history',
			array(
				'key_application'      => $applicationId,
				'key_status'           => $prevStatus->statusId,
				'status_time'          => $prevStatus->statusTime,
				'status_time_timezone' => $prevStatus->statusTimeTimeZone,
			)
		);
	}


	private function getStatusId($status)
	{
		return $this->database->fetchColumn('SELECT id_status FROM training_application_status WHERE status = ?', $status);
	}


	public function getApplicationById($id)
	{
		$result = $this->database->fetch(
			'SELECT
				t.action,
				d.id_date AS dateId,
				a.id_application AS applicationId,
				s.status,
				a.status_time AS statusTime,
				a.name,
				a.email,
				a.company,
				a.street,
				a.city,
				a.zip,
				a.company_id AS companyId,
				a.company_tax_id AS companyTaxId,
				a.note,
				a.price,
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

		return $result;
	}


	private function getTrainingApplicationSource($source)
	{
		return $this->database->fetchColumn('SELECT id_source FROM training_application_sources WHERE alias = ?', $source);
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


	public function setFilesDir($dir)
	{
		if ($dir[strlen($dir) - 1] != '/') {
			$dir .= '/';
		}
		$this->filesDir = $dir;
	}


	public function getFiles($applicationId)
	{
		$files = $this->database->fetchAll(
			'SELECT
				f.id_file AS fileId,
				f.filename AS fileName,
				CAST(DATE(d.start) AS char) AS date
			FROM 
				files f
				JOIN training_materials m ON f.id_file = m.key_file
				JOIN training_applications a ON m.key_application = a.id_application
				JOIN training_application_status s ON a.key_status = s.id_status
				JOIN training_dates d ON a.key_date = d.id_date
			WHERE
				a.id_application = ?
				AND s.status IN (?, ?, ?)',
			$applicationId,
			self::STATUS_ATTENDED,
			self::STATUS_MATERIALS_SENT,
			self::STATUS_ACCESS_TOKEN_USED
		);

		foreach ($files as $file) {
			$file->dirName = $this->filesDir . $file->date;
		}

		return $files;
	}


	public function getFile($applicationId, $token, $filename)
	{
		$file = $this->database->fetch(
			'SELECT
				f.id_file AS fileId,
				f.filename AS fileName,
				CAST(DATE(d.start) AS char) AS date
			FROM 
				files f
				JOIN training_materials m ON f.id_file = m.key_file
				JOIN training_applications a ON m.key_application = a.id_application
				JOIN training_application_status s ON a.key_status = s.id_status
				JOIN training_dates d ON a.key_date = d.id_date
			WHERE
				a.id_application = ?
				AND a.access_token = ?
				AND f.filename = ?
				AND s.status IN (?, ?, ?)',
			$applicationId,
			$token,
			$filename,
			self::STATUS_ATTENDED,
			self::STATUS_MATERIALS_SENT,
			self::STATUS_ACCESS_TOKEN_USED
		);

		if ($file) {
			$file->dirName = $this->filesDir . $file->date;
		}

		return $file;
	}


	public function sendSignUpMail($applicationId, $template, $recipientAddress, $recipientName, $start, $training, $trainingName, $venueName, $venueNameExtended, $venueAddress, $venueCity)
	{
		\Nette\Diagnostics\Debugger::log("Sending sign-up email to {$recipientName} <{$recipientAddress}>, application id: {$applicationId}, training: {$training}");

		$template->training     = $training;
		$template->trainingName = $trainingName;
		$template->start        = $start;
		$template->venueName    = $venueName;
		$template->venueNameExtended = $venueNameExtended;
		$template->venueAddress = $venueAddress;
		$template->venueCity    = $venueCity;

		$mail = new \Nette\Mail\Message();
		$mail->setFrom($this->emailFrom)
			->addTo($recipientAddress, $recipientName)
			->addBcc($this->emailFrom)
			->setBody($template)
			->clearHeader('X-Mailer')  // Hide Nette Mailer banner
			->send();
	}


	public function setEmailFrom($from)
	{
		$this->emailFrom = $from;
	}


	public function logFileDownload($applicationId, $downloadId)
	{
		$this->database->query('INSERT INTO training_material_downloads', array(
			'key_application'   => $applicationId,
			'key_file_download' => $downloadId,
		));
	}


	public function setAccessTokenUsed(\Nette\Database\Row $application)
	{
		if ($application->status != self::STATUS_ACCESS_TOKEN_USED) {
			$this->setStatus($application->applicationId, self::STATUS_ACCESS_TOKEN_USED);
		}
	}


	public function getDataRules()
	{
		return $this->dataRules;
	}


}
