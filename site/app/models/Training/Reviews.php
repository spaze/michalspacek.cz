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
	 * Get reviews by training id.
	 *
	 * @param integer $id
	 * @param integer|null $limit
	 * @return \Nette\Database\Row[]
	 */
	public function getReviews(int $id, ?int $limit = null): array
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
	public function getReviewByDateId(int $dateId): array
	{
		return $this->database->fetchAll('SELECT
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
			WHERE
				r.key_date = ? OR a.key_date = ?',
			$dateId,
			$dateId
		);
	}


	public function updateReview(int $reviewId, ?string $name, ?string $company, ?string $jobTitle, string $review, ?string $href, bool $hidden): void
	{
		$this->database->query(
			'UPDATE training_reviews SET ? WHERE id_review = ?',
			array(
				'name' => $name,
				'company' => $company,
				'job_title' => $jobTitle,
				'review' => $review,
				'href' => $href,
				'hidden' => $hidden,
			),
			$reviewId
		);
	}

}
