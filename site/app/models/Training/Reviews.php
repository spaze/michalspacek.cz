<?php
namespace MichalSpacekCz\Training;

/**
 * Trainings reviews model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Reviews
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/**
	 * @param \Nette\Database\Context $context
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 */
	public function __construct(
		\Nette\Database\Context $context,
		\MichalSpacekCz\Formatter\Texy $texyFormatter
	)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
	}


	/**
	 * Get reviews by training id.
	 *
	 * @param integer $id
	 * @param integer|null $limit
	 * @return \Nette\Database\Row[]
	 */
	public function getReviews($id, $limit = null)
	{
		$query = 'SELECT
				COALESCE(r.name, a.name) AS name,
				COALESCE(r.company, a.company) AS company,
				r.job_title AS jobTitle,
				r.review,
				r.href
			FROM
				training_reviews r
				LEFT JOIN training_applications a ON r.key_application = a.id_application
				JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON t.id_training = d.key_training
				LEFT JOIN trainings t2 ON t2.id_training = t.key_successor
			WHERE
				(t.id_training = ? OR t2.id_training = ?)
				AND NOT r.hidden
			ORDER BY r.ranking IS NULL, r.ranking, r.added DESC';

		if ($limit !== null) {
			$this->database->getConnection()->getSupplementalDriver()->applyLimit($query, $limit, null);
		}

		$reviews = $this->database->fetchAll($query, $id, $id);
		foreach ($reviews as &$review) {
			$review['review'] = $this->texyFormatter->format($review['review']);
		}
		return $reviews;
	}


	/**
	 * Get review by application id.
	 *
	 * @param integer $applicationId
	 * @return \Nette\Database\Row
	 * @throws \RuntimeException
	 */
	public function getReviewByApplicationId($applicationId)
	{
		$result = $this->database->fetch('SELECT
				a.name AS applicationName,
				r.name,
				a.company AS applicationCompany,
				r.company,
				r.job_title AS jobTitle,
				r.review,
				r.href,
				r.hidden,
				d.id_date AS dateId
			FROM
				training_applications a
				JOIN training_dates d ON a.key_date = d.id_date
				LEFT JOIN training_reviews r ON a.id_application = r.key_application
			WHERE
				a.id_application = ?',
			$applicationId
		);

		if (!$result) {
			throw new \RuntimeException("No application id {$applicationId}, yet");
		}

		return $result;
	}


	public function addUpdateReview($applicationId, $name, $company, $jobTitle, $review, $href, $hidden)
	{
		$datetime = new \DateTime();
		return $this->database->query(
				'INSERT INTO
					training_reviews ?
				ON DUPLICATE KEY UPDATE ?',
				array(
					'key_application' => $applicationId,
					'name' => $name,
					'company' => $company,
					'job_title' => $jobTitle,
					'review' => $review,
					'href' => $href,
					'hidden' => $hidden,
					'added' => $datetime,
					'added_timezone' => $datetime->getTimezone()->getName(),
				),
				array(
					'name' => $name,
					'company' => $company,
					'job_title' => $jobTitle,
					'review' => $review,
					'href' => $href,
					'hidden' => $hidden,
				)
		);
	}

}
