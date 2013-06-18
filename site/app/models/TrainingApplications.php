<?php
namespace MichalSpacekCz;

/**
 * Training applications model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingApplications extends BaseModel
{


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
				s.status
			FROM
				training_applications a
				JOIN training_application_status s ON a.key_status = s.id_status
			WHERE
				key_date = ?',
			$dateId
		);
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


	public function getReviewByApplicationId($applicationId)
	{
		return $this->database->fetch('SELECT
				r.name,
				r.company,
				r.review,
				r.href,
				r.hidden
			FROM
				training_reviews r
			WHERE
				r.key_application = ?',
			$applicationId
		);
	}


}
