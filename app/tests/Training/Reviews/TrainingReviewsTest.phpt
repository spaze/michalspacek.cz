<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Preliminary;

use DateTimeImmutable;
use MichalSpacekCz\DateTime\DateTimeFormat;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Exceptions\TrainingReviewNotFoundException;
use MichalSpacekCz\Training\Exceptions\TrainingReviewRankingInvalidException;
use MichalSpacekCz\Training\Reviews\TrainingReview;
use MichalSpacekCz\Training\Reviews\TrainingReviews;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingReviewsTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TrainingReviews $trainingReviews,
		private readonly DateTimeMachineFactory $dateTimeMachineFactory,
	) {
	}


	#[Override]
	protected function setUp(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'id' => 11,
				'name' => 'Name 1',
				'company' => 'Company 1',
				'jobTitle' => 'Title',
				'review' => 'Review //1//',
				'href' => 'https://example.com/1',
				'hidden' => 0,
				'ranking' => 1,
				'note' => 'Note 1',
				'dateId' => 21,
			],
			[
				'id' => 12,
				'name' => 'Name 2',
				'company' => 'Company 2',
				'jobTitle' => null,
				'review' => 'Review //2//',
				'href' => 'https://example.com/2',
				'hidden' => 1,
				'ranking' => null,
				'note' => null,
				'dateId' => 22,
			],
		]);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGetVisibleReviews(): void
	{
		$reviews = $this->trainingReviews->getVisibleReviews(3, 4);
		$this->assertReviews($reviews);
	}


	public function testGetAllReviews(): void
	{
		$reviews = $this->trainingReviews->getAllReviews(3);
		$this->assertReviews($reviews);
	}


	public function testGetReviewsById(): void
	{
		$reviews = $this->trainingReviews->getReviewsByDateId(5);
		$this->assertReviews($reviews);
	}


	public function testGetAllReviewsInvalidRanking(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'id' => 11,
				'name' => 'Name 1',
				'company' => 'Company 1',
				'jobTitle' => 'Title',
				'review' => 'Review //1//',
				'href' => 'https://example.com/1',
				'hidden' => 0,
				'ranking' => -1,
				'note' => 'Note 1',
				'dateId' => 21,
			],
		]);
		Assert::exception(function (): void {
			$this->trainingReviews->getAllReviews(3);
		}, TrainingReviewRankingInvalidException::class, "The rating of the training review id '11' is invalid: '-1'");
	}


	public function testGetReview(): void
	{
		$this->database->setFetchDefaultResult([
			'id' => 11,
			'name' => 'Name 1',
			'company' => 'Company 1',
			'jobTitle' => 'Title',
			'review' => 'Review //1//',
			'href' => 'https://example.com/1',
			'hidden' => 0,
			'ranking' => 1,
			'note' => 'Note 1',
			'dateId' => 21,
		]);
		$review = $this->trainingReviews->getReview(5);
		Assert::same(11, $review->getId());
		Assert::same('Name 1', $review->getName());
		Assert::same('Company 1', $review->getCompany());
		Assert::same('Title', $review->getJobTitle());
		Assert::same('Review <em>1</em>', $review->getReview()->getHtml());
		Assert::same('https://example.com/1', $review->getHref());
		Assert::false($review->isHidden());
		Assert::same(1, $review->getRanking());
		Assert::same('Note 1', $review->getNote());
		Assert::same(21, $review->getDateId());
	}


	public function testGetReviewNotFound(): void
	{
		Assert::exception(function (): void {
			$this->trainingReviews->getReview(6);
		}, TrainingReviewNotFoundException::class, "Training review id '6' doesn't exist");
	}


	public function testUpdateReview(): void
	{
		$this->trainingReviews->updateReview(
			2,
			3,
			'Name',
			'Company',
			null,
			'Review',
			'https://review.example',
			false,
			null,
			null,
		);
		Assert::count(1, $this->database->getParamsArrayForQuery('UPDATE training_reviews SET ? WHERE id_review = ?'));
	}


	public function testAddReview(): void
	{
		$date = new DateTimeImmutable();
		$this->dateTimeMachineFactory->setDateTime($date);
		$this->trainingReviews->addReview(
			4,
			'Name',
			'Company',
			null,
			'Review',
			'https://review.example',
			false,
			null,
			null,
		);
		$params = $this->database->getParamsArrayForQuery('INSERT INTO training_reviews ?');
		Assert::count(1, $params);
		Assert::same($date->format(DateTimeFormat::MYSQL), $params[0]['added']);
		Assert::same($date->getTimezone()->getName(), $params[0]['added_timezone']);
	}


	/**
	 * @param list<TrainingReview> $reviews
	 */
	private function assertReviews(array $reviews): void
	{
		Assert::count(2, $reviews);

		Assert::same(11, $reviews[0]->getId());
		Assert::same('Name 1', $reviews[0]->getName());
		Assert::same('Company 1', $reviews[0]->getCompany());
		Assert::same('Title', $reviews[0]->getJobTitle());
		Assert::same('Review <em>1</em>', $reviews[0]->getReview()->getHtml());
		Assert::same('https://example.com/1', $reviews[0]->getHref());
		Assert::false($reviews[0]->isHidden());
		Assert::same(1, $reviews[0]->getRanking());
		Assert::same('Note 1', $reviews[0]->getNote());
		Assert::same(21, $reviews[0]->getDateId());

		Assert::same(12, $reviews[1]->getId());
		Assert::same('Name 2', $reviews[1]->getName());
		Assert::same('Company 2', $reviews[1]->getCompany());
		Assert::null($reviews[1]->getJobTitle());
		Assert::same('Review <em>2</em>', $reviews[1]->getReview()->getHtml());
		Assert::same('https://example.com/2', $reviews[1]->getHref());
		Assert::true($reviews[1]->isHidden());
		Assert::null($reviews[1]->getRanking());
		Assert::null($reviews[1]->getNote());
		Assert::same(22, $reviews[1]->getDateId());
	}

}

TestCaseRunner::run(TrainingReviewsTest::class);
