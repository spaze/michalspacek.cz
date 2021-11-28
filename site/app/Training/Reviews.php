<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use DateTime;
use DateTimeZone;
use MichalSpacekCz\Formatter\Texy;
use Nette\Database\Explorer;
use Nette\Database\Row;
use RuntimeException;

class Reviews
{

	private Explorer $database;

	private Texy $texyFormatter;


	public function __construct(
		Explorer $context,
		Texy $texyFormatter,
	) {
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
	}


	/**
	 * Get visible reviews by training id.
	 *
	 * @param int $id
	 * @param int|null $limit
	 * @return Row[]
	 */
	public function getVisibleReviews(int $id, ?int $limit = null): array
	{
		$query = 'SELECT
				r.id_review AS reviewId,
				r.name AS name,
				r.company AS company,
				r.job_title AS jobTitle,
				r.review,
				r.href
			FROM
				training_reviews r
				JOIN training_dates d ON r.key_date = d.id_date
				JOIN trainings t ON t.id_training = d.key_training
				LEFT JOIN trainings t2 ON t2.id_training = t.key_successor
			WHERE
				(t.id_training = ? OR t2.id_training = ?)
				AND NOT r.hidden
				AND t.key_discontinued IS NULL
			ORDER BY r.ranking IS NULL, r.ranking, r.added DESC
			LIMIT ?';

		return $this->format($this->database->fetchAll($query, $id, $id, $limit ?? PHP_INT_MAX));
	}


	/**
	 * Get all reviews including hidden by training id.
	 *
	 * @param int $id
	 * @return Row[]
	 */
	public function getAllReviews(int $id): array
	{
		$query = 'SELECT
				r.id_review AS reviewId,
				r.name AS name,
				r.company AS company,
				r.job_title AS jobTitle,
				r.review,
				r.href,
				r.hidden,
				r.ranking
			FROM
				training_reviews r
				JOIN training_dates d ON r.key_date = d.id_date
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
	 * @param Row[] $reviews
	 * @return Row[]
	 */
	private function format(array $reviews): array
	{
		foreach ($reviews as &$review) {
			$review['review'] = $this->texyFormatter->format($review['review']);
		}
		return $reviews;
	}


	/**
	 * Get review by id.
	 *
	 * @param int $reviewId
	 * @return Row<mixed>
	 * @throws RuntimeException
	 */
	public function getReview(int $reviewId): Row
	{
		/** @var Row<mixed>|null $result */
		$result = $this->database->fetch(
			'SELECT
				r.id_review AS reviewId,
				r.name,
				r.company,
				r.job_title AS jobTitle,
				r.review,
				r.href,
				r.hidden,
				r.ranking,
				r.note,
				d.id_date AS dateId
			FROM
				training_reviews r
				LEFT JOIN training_dates d ON r.key_date = d.id_date
			WHERE
				r.id_review = ?',
			$reviewId,
		);

		if (!$result) {
			throw new RuntimeException("No review id {$reviewId}, yet");
		}

		return $result;
	}


	/**
	 * Get review by date id.
	 *
	 * @param int $dateId
	 * @return Row[]
	 */
	public function getReviewsByDateId(int $dateId): array
	{
		$query = 'SELECT
				r.id_review AS reviewId,
				r.name AS name,
				r.company AS company,
				r.job_title AS jobTitle,
				r.review,
				r.href,
				r.hidden,
				r.ranking
			FROM
				training_reviews r
			WHERE
				r.key_date = ?';

		return $this->format($this->database->fetchAll($query, $dateId));
	}


	public function updateReview(int $reviewId, int $dateId, string $name, string $company, ?string $jobTitle, string $review, ?string $href, bool $hidden, ?int $ranking, ?string $note): void
	{
		$this->database->query(
			'UPDATE training_reviews SET ? WHERE id_review = ?',
			array(
				'key_date' => $dateId,
				'name' => $name,
				'company' => $company,
				'job_title' => $jobTitle,
				'review' => $review,
				'href' => $href,
				'hidden' => $hidden,
				'ranking' => $ranking,
				'note' => $note,
			),
			$reviewId,
		);
	}


	public function addReview(int $dateId, string $name, string $company, ?string $jobTitle, string $review, ?string $href, bool $hidden, ?int $ranking, ?string $note): void
	{
		$datetime = new DateTime();
		/** @var DateTimeZone|false $timeZone */
		$timeZone = $datetime->getTimezone();
		$this->database->query(
			'INSERT INTO training_reviews ?',
			array(
				'key_date' => $dateId,
				'name' => $name,
				'company' => $company,
				'job_title' => $jobTitle,
				'review' => $review,
				'href' => $href,
				'added' => $datetime,
				'added_timezone' => ($timeZone ? $timeZone->getName() : date_default_timezone_get()),
				'hidden' => $hidden,
				'ranking' => $ranking,
				'note' => $note,
			),
		);
	}

}
