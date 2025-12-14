<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Training;

use DateTimeImmutable;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Reviews\TrainingReview;
use Nette\Utils\Arrays;
use Nette\Utils\Html;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingReviewFormFactoryTest extends TestCase
{

	private const int DATE_ID = 123;

	private ?int $resultDateId = null;


	public function __construct(
		private readonly Database $database,
		private readonly TrainingReviewFormFactory $formFactory,
		private readonly ApplicationPresenter $applicationPresenter,
		DateTimeMachineFactory $dateTimeFactory,
	) {
		$dateTimeFactory->setDateTime(new DateTimeImmutable('2020-01-01 12:34:56'));
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->resultDateId = null;
	}


	public function testCreateOnSuccessAdd(): void
	{
		$form = $this->formFactory->create(
			function (int $dateId): void {
				$this->resultDateId = $dateId;
			},
			self::DATE_ID,
			null,
		);
		$this->applicationPresenter->anchorForm($form);
		Arrays::invoke($form->onSuccess, $form);
		Assert::same(self::DATE_ID, $this->resultDateId);
		Assert::same([
			[
				'key_date' => self::DATE_ID,
				'name' => '',
				'company' => '',
				'job_title' => null,
				'review' => '',
				'href' => null,
				'added' => '2020-01-01 12:34:56',
				'added_timezone' => 'Europe/Prague',
				'hidden' => false,
				'ranking' => null,
				'note' => null,
			],
		], $this->database->getParamsArrayForQuery('INSERT INTO training_reviews ?'));
	}


	public function testCreateOnSuccessEdit(): void
	{
		$form = $this->formFactory->create(
			function (int $dateId): void {
				$this->resultDateId = $dateId;
			},
			self::DATE_ID,
			new TrainingReview(
				303,
				'John Deere',
				'Comp Any',
				'Team Le-ad',
				Html::fromHtml('<strong>foo</strong>'),
				'**foo**',
				'https://example.com',
				false,
				3,
				'No tea',
				self::DATE_ID,
			),
		);
		$this->applicationPresenter->anchorForm($form);
		Arrays::invoke($form->onSuccess, $form);
		Assert::same(self::DATE_ID, $this->resultDateId);
		Assert::same([
			[
				'key_date' => self::DATE_ID,
				'name' => 'John Deere',
				'company' => 'Comp Any',
				'job_title' => 'Team Le-ad',
				'review' => '**foo**',
				'href' => 'https://example.com',
				'hidden' => false,
				'ranking' => 3,
				'note' => 'No tea',
			],
		], $this->database->getParamsArrayForQuery('UPDATE training_reviews SET ? WHERE id_review = ?'));
	}

}

TestCaseRunner::run(TrainingReviewFormFactoryTest::class);
