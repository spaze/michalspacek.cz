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

	public function getUpcoming()
	{
		$result = $this->database->fetchAll(
			'SELECT
				t.action,
				t.name,
				d.tentative,
				d.start
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN (
					SELECT
						MIN(d.start) AS start
					FROM
						training_dates d
					WHERE
						d.end > NOW()
					GROUP BY
						d.key_training
				) d2 ON d2.start = d.start
			ORDER BY
				t.id_training, d.start'
		);

		foreach ($result as &$row) {
			$row['tentative'] = (boolean)$row['tentative'];
		}

		return $result;
	}


	public function get($name)
	{
		$result = $this->database->fetch(
			'SELECT
				t.action,
				d.id_date AS dateId,
				t.name,
				t.description,
				t.content,
				t.prerequisites,
				t.audience,
				d.start,
				d.end,
				d.tentative,
				t.original_href AS originalHref,
				t.capacity,
				t.services,
				t.price,
				t.student_discount AS studentDiscount,
				t.materials,
				v.href AS venueHref,
				v.name AS venueName,
				v.address AS venueAddress,
				v.description AS venueDescription
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_venues v ON d.key_venue = v.id_venue
			WHERE t.action = ?
				AND d.end > NOW()
			ORDER BY
				d.start
			LIMIT 1',
			$name
		);

		if (isset($result['tentative'])) {
			$result['tentative'] = (boolean)$result['tentative'];
		}

		return $result;
	}


	public function getPastTrainings($name)
	{
		return $this->database->fetchPairs(
			'SELECT
				d.id_date AS dateId,
				d.start
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
			WHERE t.action = ?
				AND d.end < NOW()
			ORDER BY
				start DESC',
			$name
		);
	}


	private function insertData($data)
	{
		$data['access_token'] = $this->generateAccessCode();
		try {
			$this->database->query('INSERT INTO training_applications', $data);
			$this->setStatus($this->database->lastInsertId(), self::STATUS_TENTATIVE);
		} catch (\PDOException $e) {
			if ($e->getCode() == '23000') {
				// regenerate the access code and try harder this time
				return $this->insertData($data);
			} else {
				throw $e;
			}
		}
		return $data['access_token'];
	}


	public function addInvitation($trainingId, $name, $email, $note)
	{
		$statusId = $this->getStatusId(self::STATUS_CREATED);
		$datetime = new \DateTime();

		$this->database->beginTransaction();
		$data = array(
			'key_date'             => $trainingId,
			'name'                 => $name,
			'email'                => $email,
			'note'                 => $note,
			'key_status'           => $statusId,
			'status_time'          => $datetime,
			'status_time_timezone' => $datetime->getTimezone()->getName(),
		);
		$code = $this->insertData($data);
		$this->database->commit();

		return true;
	}


	public function addApplication($trainingId, $name, $email, $company, $street, $city, $zip, $companyId, $companyTaxId, $note)
	{
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
		);
		$code = $this->insertData($data);
		$this->database->commit();

		return true;
	}


	public function getReviews($name, $limit = null)
	{
		$query = 'SELECT
				r.name,
				r.company,
				r.review
			FROM
				training_reviews r
				JOIN training_dates d ON r.key_date = d.id_date
				JOIN trainings t ON t.id_training = d.key_training
			WHERE
				t.action = ?
			ORDER BY r.added';

		if ($limit !== null) {
			$this->database->getSupplementalDriver()->applyLimit($query, $limit, null);
		}

		return $this->database->fetchAll($query, $name);
	}


	private function generateAccessCode()
	{
		return \Nette\Utils\Strings::random();
	}


	private function setStatus($applicationId, $status)
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


	public function getApplicationByToken($token)
	{
		$result = $this->database->fetch(
			'SELECT
				t.action,
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


}