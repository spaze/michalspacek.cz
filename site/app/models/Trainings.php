<?php
namespace MichalSpacekCz;

/**
 * Trainings model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Trainings extends BaseModel
{

	const STATUS_CREATED   = 'CREATED';
	const STATUS_TENTATIVE = 'TENTATIVE';
	const STATUS_SIGNED_UP = 'SIGNED_UP';
	const STATUS_ATTENDED = 'ATTENDED';
	const STATUS_MATERIALS_SENT = 'MATERIALS_SENT';
	const STATUS_ACCESS_TOKEN_USED = 'ACCESS_TOKEN_USED';
	const STATUS_IMPORTED = 'IMPORTED';
	const STATUS_NON_PUBLIC_TRAINING = 'NON_PUBLIC_TRAINING';
	const TRAINING_APPLICATION_SOURCE  = 'michal-spacek';

	const LAST_FREE_SEATS_THRESHOLD_DAYS = 7;

	protected $filesDir;

	protected $emailFrom;

	protected $initialStatuses = array(
		self::STATUS_TENTATIVE,
		self::STATUS_SIGNED_UP,
		self::STATUS_IMPORTED,
		self::STATUS_NON_PUBLIC_TRAINING
	);


	public function getUpcoming()
	{
		$query = "SELECT
				d.id_date AS dateId,
				t.action,
				t.name,
				s.status,
				d.start,
				v.city as venueCity
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_date_status s ON d.key_status = s.id_status
				JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN (
					SELECT
						t2.action,
						d2.key_venue,
						MIN(d2.start) AS start
					FROM
						trainings t2
						JOIN training_dates d2 ON t2.id_training = d2.key_training
						JOIN training_date_status s2 ON d2.key_status = s2.id_status
					WHERE
						d2.public
						AND d2.end > NOW()
						AND s2.status IN ('TENTATIVE', 'CONFIRMED')
					GROUP BY
						t2.action, d2.key_venue
				) u ON t.action = u.action AND v.id_venue = u.key_venue AND d.start = u.start
			ORDER BY
				t.id_training, d.start";

		$upcoming = array();
		foreach ($this->database->fetchAll($query) as $row) {
			$date = array(
				'dateId'        => $row->dateId,
				'tentative'     => ($row->status == self::STATUS_TENTATIVE),
				'lastFreeSeats' => $this->lastFreeSeats($row->start),
				'start'         => $row->start,
				'status'        => $row->status,
				'venueCity'     => $row->venueCity,
			);
			$upcoming[$row->action] = \Nette\ArrayHash::from(array(
				'action' => $row->action,
				'name'   => $row->name,
				'dates'  => (isset($upcoming[$row->action]->dates)
					? $upcoming[$row->action]->dates = (array)$upcoming[$row->action]->dates + array($row->dateId => $date)
					: array($row->dateId => $date)
				),
			));
		}

		return $upcoming;
	}


	public function get($name)
	{
		$result = $this->database->fetch(
			'SELECT
				action,
				name,
				description,
				content,
				upsell,
				prerequisites,
				audience,
				original_href AS originalHref,
				capacity,
				price,
				student_discount AS studentDiscount,
				materials
			FROM trainings
			WHERE action = ?',
			$name
		);

		if ($result) {
			$result->description   = $this->texyFormatter->format($result->description);
			$result->content       = $this->texyFormatter->format($result->content);
			$result->upsell        = $this->texyFormatter->format($result->upsell);
			$result->prerequisites = $this->texyFormatter->format($result->prerequisites);
			$result->audience      = $this->texyFormatter->format($result->audience);
			$result->materials     = $this->texyFormatter->format($result->materials);
		}

		return $result;
	}


	public function getDates($name)
	{
		$result = $this->database->fetchAll(
			"SELECT
				d.id_date AS dateId,
				d.start,
				d.end,
				s.status,
				v.href AS venueHref,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.address AS venueAddress,
				v.city AS venueCity,
				v.description AS venueDescription,
				c.description AS cooperationDescription
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_date_status s ON d.key_status = s.id_status
				LEFT JOIN training_cooperations c ON d.key_cooperation = c.id_cooperation
				JOIN (
					SELECT
						t2.action,
						d2.key_venue,
						MIN(d2.start) AS start
					FROM
						trainings t2
						JOIN training_dates d2 ON t2.id_training = d2.key_training
						JOIN training_date_status s2 ON d2.key_status = s2.id_status
					WHERE
						d2.public
						AND t2.action = ?
						AND d2.end > NOW()
						AND s2.status IN ('TENTATIVE', 'CONFIRMED')
					GROUP BY
						t2.action, d2.key_venue
				) u ON t.action = u.action AND v.id_venue = u.key_venue AND d.start = u.start
			ORDER BY
				d.start",
			$name
		);
		$dates = array();
		foreach ($result as $row) {
			$row->tentative        = ($row->status == self::STATUS_TENTATIVE);
			$row->lastFreeSeats    = $this->lastFreeSeats($row->start);
			$row->venueDescription = $this->texyFormatter->format($row->venueDescription);
			$row->cooperationDescription = $this->texyFormatter->format($row->cooperationDescription);
			$dates[$row->dateId]   = $row;
		}
		return $dates;
	}


	protected function lastFreeSeats(\DateTime $start)
	{
		$now = new \DateTime();
		return ($start->diff($now)->days <= self::LAST_FREE_SEATS_THRESHOLD_DAYS && $start > $now);
	}


	public function lastFreeSeatsAnyTraining(array $trainings)
	{
		$lastFreeSeats = false;
		foreach ($trainings as $training) {
			if ($this->lastFreeSeatsAnyDate((array)$training->dates)) {
				$lastFreeSeats = true;
				break;
			}
		}
		return $lastFreeSeats;
	}


	public function lastFreeSeatsAnyDate(array $dates)
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


	public function getPastDates($name)
	{
		return $this->database->fetchPairs(
			"SELECT
				d.id_date AS dateId,
				d.start
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_date_status s ON d.key_status = s.id_status
			WHERE t.action = ?
				AND d.end < NOW()
				AND s.status = 'CONFIRMED'
				AND d.public
			ORDER BY
				start DESC",
			$name
		);
	}


	public function getAllTrainings()
	{
		$result = $this->database->fetchAll(
			'SELECT
				d.id_date AS dateId,
				t.action,
				t.name,
				d.start,
				d.end,
				s.status,
				v.href AS venueHref,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.city AS venueCity
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_date_status s ON d.key_status = s.id_status
			WHERE
				d.end < NOW()
			ORDER BY
				d.start'
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
			self::TRAINING_APPLICATION_SOURCE
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
			self::TRAINING_APPLICATION_SOURCE
		);
	}


	public function insertApplication($trainingId, $name, $email, $company, $street, $city, $zip, $companyId, $companyTaxId, $note, $status, $source)
	{
		if (!in_array($status, $this->getInitialStatuses())) {
			throw new \RuntimeException("Invalid initial status {$status}");
		}

		$statusId = $this->getStatusId(self::STATUS_CREATED);
		$datetime = new \DateTime();

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
		$this->setStatus($applicationId, $status);
		$this->database->commit();

		return $applicationId;
	}


	public function updateApplication($applicationId, $name, $email, $company, $street, $city, $zip, $companyId, $companyTaxId, $note)
	{
		$this->database->beginTransaction();
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
			),
			$applicationId
		);
		$this->setStatus($applicationId, self::STATUS_SIGNED_UP);
		$this->database->commit();
		return $applicationId;
	}


	public function getInitialStatuses()
	{
		return $this->initialStatuses;
	}


	public function getReviews($name, $limit = null)
	{
		$query = 'SELECT
				COALESCE(r.name, a.name) AS name,
				COALESCE(r.company, a.company) AS company,
				r.review,
				r.href
			FROM
				training_reviews r
				LEFT JOIN training_applications a ON r.key_application = a.id_application
				JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON t.id_training = d.key_training
			WHERE
				t.action = ?
				AND NOT r.hidden
			ORDER BY r.ranking IS NULL, r.ranking, r.added DESC';

		if ($limit !== null) {
			$this->database->getSupplementalDriver()->applyLimit($query, $limit, null);
		}

		$reviews = $this->database->fetchAll($query, $name);
		foreach ($reviews as &$review) {
			$review['review'] = $this->texyFormatter->format($review['review']);
		}
		return $reviews;
	}


	private function generateAccessCode()
	{
		return \Nette\Utils\Strings::random(mt_rand(32, 64), '0-9a-zA-Z');
	}


	public function setStatus($applicationId, $status)
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

		$datetime = new \DateTime();
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
				d.start AS trainingStart,
				s.status,
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


}