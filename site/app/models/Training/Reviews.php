<?php
declare(strict_types = 1);

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
	 * Get visible reviews by training id.
	 *
	 * @param integer $id
	 * @param integer|null $limit
	 * @return \Nette\Database\Row[]
	 */
	public function getVisibleReviews(int $id, ?int $limit = null): array
	{
		$query = 'SELECT
				r.id_review AS reviewId,
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
				AND t.key_discontinued IS NULL
			ORDER BY r.ranking IS NULL, r.ranking, r.added DESC';

		if ($limit !== null) {
			$this->database->getConnection()->getSupplementalDriver()->applyLimit($query, $limit, null);
		}

		return $this->format($this->database->fetchAll($query, $id, $id));
	}


	/**
	 * Get all reviews including hidden by training id.
	 *
	 * @param integer $id
	 * @return \Nette\Database\Row[]
	 */
	public function getAllReviews(int $id): array
	{
		$query = 'SELECT
				r.id_review AS reviewId,
				COALESCE(r.name, a.name) AS name,
				COALESCE(r.company, a.company) AS company,
				r.job_title AS jobTitle,
				r.review,
				r.href,
				r.hidden
			FROM
				training_reviews r
				LEFT JOIN training_applications a ON r.key_application = a.id_application
				JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON t.id_training = d.key_training
				LEFT JOIN trainings t2 ON t2.id_training = t.key_successor
			WHERE
				(t.id_training = ? OR t2.id_training = ?)
			ORDER BY r.ranking IS NULL, r.ranking, r.added DESC';

		return $this->format($this->database->fetchAll($query, $id, $id));
	}


	/**
	 * Format reviews.
	 *
	 * @param \Nette\Database\Row[] $reviews
	 * @return array
	 */
	private function format(array $reviews): array
	{
		foreach ($reviews as &$review) {
			$review['review'] = $this->texyFormatter->format($review['review']);
		}
		return $reviews;
	}


	/**
	 * Get review by application id.
	 *
	 * @param integer $applicationId
	 * @return int|null
	 */
	public function getReviewIdByApplicationId(int $applicationId): ?int
	{
		return $this->database->fetchField('SELECT
				r.id_review
			FROM
				training_applications a
				JOIN training_reviews r ON a.id_application = r.key_application
			WHERE
				a.id_application = ?',
			$applicationId
		) ?: null;
	}


	/**
	 * Get review by id.
	 *
	 * @param integer $reviewId
	 * @return \Nette\Database\Row
	 * @throws \RuntimeException
	 */
	public function getReview(int $reviewId): \Nette\Database\Row
	{
		$result = $this->database->fetch('SELECT
				r.id_review AS reviewId,
				r.key_application AS applicationId,
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
				training_reviews r
				LEFT JOIN training_applications a ON r.key_application = a.id_application
				LEFT JOIN training_dates d ON a.key_date = d.id_date OR r.key_date = d.id_date
			WHERE
				r.id_review = ?',
			$reviewId
		);

		if (!$result) {
			throw new \RuntimeException("No review id {$reviewId}, yet");
		}

		return $result;
	}


	/**
	 * Get review by date id.
	 *
	 * @param integer $dateId
	 * @return \Nette\Database\Row[]
	 */
	public function getReviewsByDateId(int $dateId): array
	{
		$query = 'SELECT
				r.id_review AS reviewId,
				r.key_application AS applicationId,
				COALESCE(r.name, a.name) AS name,
				COALESCE(r.company, a.company) AS company,
				r.job_title AS jobTitle,
				r.review,
				r.href,
				r.hidden
			FROM
				training_reviews r
				LEFT JOIN training_applications a ON r.key_application = a.id_application
			WHERE
				r.key_date = ? OR a.key_date = ?';

		return $this->format($this->database->fetchAll($query, $dateId, $dateId));
	}


	public function updateReview(int $reviewId, int $dateId, ?int $applicationId, bool $overwriteName, ?string $name, bool $overwriteCompany, ?string $company, ?string $jobTitle, string $review, ?string $href, bool $hidden): void
	{
		$this->database->query(
			'UPDATE training_reviews SET ? WHERE id_review = ?',
			array(
				'key_date' => $applicationId ? null : $dateId,
				'key_application' => $applicationId,
				'name' => ($overwriteName || !$applicationId) ? $name : null,
				'company' => ($overwriteCompany || !$applicationId) ? $company : null,
				'job_title' => $jobTitle,
				'review' => $review,
				'href' => $href,
				'hidden' => $hidden,
			),
			$reviewId
		);
	}


	public function addReview(int $dateId, ?int $applicationId, ?string $name, ?string $company, ?string $jobTitle, string $review, ?string $href, bool $hidden): void
	{
		$datetime = new \DateTime();
		$this->database->query(
			'INSERT INTO training_reviews ?',
			array(
				'key_date' => $applicationId ? null : $dateId,
				'key_application' => $applicationId,
				'name' => $name,
				'company' => $company,
				'job_title' => $jobTitle,
				'review' => $review,
				'href' => $href,
				'added' => $datetime,
				'added_timezone' => $datetime->getTimezone()->getName(),
				'hidden' => $hidden,
			)
		);
	}

}
