<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Reviews;

use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Training\Exceptions\TrainingReviewNotFoundException;
use MichalSpacekCz\Training\Exceptions\TrainingReviewRankingInvalidException;
use Nette\Database\Explorer;
use Nette\Database\Row;

readonly class TrainingReviews
{

	public function __construct(
		private Explorer $database,
		private TypedDatabase $typedDatabase,
		private TexyFormatter $texyFormatter,
		private DateTimeFactory $dateTimeFactory,
	) {
	}


	/**
	 * @return list<TrainingReview>
	 * @throws TrainingReviewRankingInvalidException
	 */
	public function getVisibleReviews(int $id, ?int $limit = null): array
	{
		$query = 'SELECT
				r.id_review AS id,
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
				JOIN training_dates d ON r.key_date = d.id_date
				JOIN trainings t ON t.id_training = d.key_training
				LEFT JOIN trainings t2 ON t2.id_training = t.key_successor
			WHERE
				(t.id_training = ? OR t2.id_training = ?)
				AND NOT r.hidden
				AND t.key_discontinued IS NULL
			ORDER BY r.ranking IS NULL, r.ranking, r.added DESC
			LIMIT ?';

		$reviews = [];
		foreach ($this->typedDatabase->fetchAll($query, $id, $id, $limit ?? PHP_INT_MAX) as $row) {
			$reviews[] = $this->createFromDatabaseRow($row);
		}
		return $reviews;
	}


	/**
	 * Get all reviews including hidden by training id.
	 *
	 * @return list<TrainingReview>
	 * @throws TrainingReviewRankingInvalidException
	 */
	public function getAllReviews(int $id): array
	{
		$query = 'SELECT
				r.id_review AS id,
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
				JOIN training_dates d ON r.key_date = d.id_date
				JOIN trainings t ON t.id_training = d.key_training
				LEFT JOIN trainings t2 ON t2.id_training = t.key_successor
			WHERE
				(t.id_training = ? OR t2.id_training = ?)
			ORDER BY r.ranking IS NULL, r.ranking, r.added DESC';

		$reviews = [];
		foreach ($this->typedDatabase->fetchAll($query, $id, $id) as $row) {
			$reviews[] = $this->createFromDatabaseRow($row);
		}
		return $reviews;
	}


	/**
	 * @throws TrainingReviewNotFoundException
	 * @throws TrainingReviewRankingInvalidException
	 */
	public function getReview(int $reviewId): TrainingReview
	{
		$result = $this->database->fetch(
			'SELECT
				r.id_review AS id,
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
			throw new TrainingReviewNotFoundException($reviewId);
		}
		return $this->createFromDatabaseRow($result);
	}


	/**
	 * @return list<TrainingReview>
	 * @throws TrainingReviewRankingInvalidException
	 */
	public function getReviewsByDateId(int $dateId): array
	{
		$query = 'SELECT
				r.id_review AS id,
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
				r.key_date = ?';

		$reviews = [];
		foreach ($this->typedDatabase->fetchAll($query, $dateId) as $row) {
			$reviews[] = $this->createFromDatabaseRow($row);
		}
		return $reviews;
	}


	public function updateReview(int $reviewId, int $dateId, string $name, string $company, ?string $jobTitle, string $review, ?string $href, bool $hidden, ?int $ranking, ?string $note): void
	{
		$this->database->query(
			'UPDATE training_reviews SET ? WHERE id_review = ?',
			[
				'key_date' => $dateId,
				'name' => $name,
				'company' => $company,
				'job_title' => $jobTitle,
				'review' => $review,
				'href' => $href,
				'hidden' => $hidden,
				'ranking' => $ranking,
				'note' => $note,
			],
			$reviewId,
		);
	}


	public function addReview(int $dateId, string $name, string $company, ?string $jobTitle, string $review, ?string $href, bool $hidden, ?int $ranking, ?string $note): void
	{
		$datetime = $this->dateTimeFactory->create();
		$timeZone = $datetime->getTimezone()->getName();
		$this->database->query(
			'INSERT INTO training_reviews ?',
			[
				'key_date' => $dateId,
				'name' => $name,
				'company' => $company,
				'job_title' => $jobTitle,
				'review' => $review,
				'href' => $href,
				'added' => $datetime,
				'added_timezone' => $timeZone,
				'hidden' => $hidden,
				'ranking' => $ranking,
				'note' => $note,
			],
		);
	}


	/**
	 * @throws TrainingReviewRankingInvalidException
	 */
	private function createFromDatabaseRow(Row $row): TrainingReview
	{
		assert(is_int($row->id));
		assert(is_string($row->name));
		assert(is_string($row->company));
		assert($row->jobTitle === null || is_string($row->jobTitle));
		assert(is_string($row->review));
		assert($row->href === null || is_string($row->href));
		assert(is_int($row->hidden));
		assert($row->ranking === null || is_int($row->ranking));
		assert($row->note === null || is_string($row->note));
		assert(is_int($row->dateId));
		if ($row->ranking !== null && $row->ranking <= 0) {
			throw new TrainingReviewRankingInvalidException($row->id, $row->ranking);
		}
		return new TrainingReview(
			$row->id,
			$row->name,
			$row->company,
			$row->jobTitle,
			$this->texyFormatter->format($row->review),
			$row->review,
			$row->href,
			(bool)$row->hidden,
			$row->ranking,
			$row->note,
			$row->dateId,
		);
	}

}
