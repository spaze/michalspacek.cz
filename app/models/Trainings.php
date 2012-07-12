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
			WHERE
				d.end > NOW()
			ORDER BY t.id_training'
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
			LIMIT 1',
			$name
		);

		$result['tentative'] = (boolean)$result['tentative'];

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


	public function addInvitation($trainingId, $name, $email, $note)
	{
		$datetime = new \DateTime();
		return $this->database->query(
			'INSERT INTO training_invitations',
			array(
				'key_training'     => $trainingId,
				'name'             => $name,
				'email'            => $email,
				'note'             => $note,
				'created'          => $datetime,
				'created_timezone' => $datetime->getTimezone()->getName(),
			)
		);
	}


	public function addApplication($trainingId, $name, $email, $company, $street, $city, $zip, $companyId, $companyTaxId, $note)
	{
		$datetime = new \DateTime();
		return $this->database->query(
			'INSERT INTO training_applications',
			array(
				'key_training'     => $trainingId,
				'name'             => $name,
				'email'            => $email,
				'company'          => $company,
				'street'           => $street,
				'city'             => $city,
				'zip'              => $zip,
				'company_id'       => $companyId,
				'company_tax_id'   => $companyTaxId,
				'note'             => $note,
				'created'          => $datetime,
				'created_timezone' => $datetime->getTimezone()->getName(),
			)
		);
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


}