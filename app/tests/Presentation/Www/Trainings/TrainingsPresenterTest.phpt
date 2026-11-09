<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Trainings;

use DateTime;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\LocaleLinkGeneratorMock;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\NullSession;
use MichalSpacekCz\Test\Http\Request as HttpRequestMock;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\Training\TrainingFilesNullStorage;
use MichalSpacekCz\Test\Training\TrainingTestDataFactory;
use Nette\Application\Request;
use Nette\Application\Responses\TextResponse;
use Nette\Http\IRequest;
use Override;
use Stringable;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class TrainingsPresenterTest extends TestCase
{

	private const string ACTION = 'php';
	private const int APPLICATION_ID = 303;
	private const string SWITCHER_LINK = 'https://www.burger.test/switcher';


	public function __construct(
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly Database $database,
		private readonly LocaleLinkGeneratorMock $localeLinkGenerator,
		private readonly NullSession $session,
		private readonly TrainingFilesNullStorage $storage,
		private readonly TrainingTestDataFactory $dataFactory,
		HttpRequestMock $httpRequest,
	) {
		$httpRequest->setMethod(IRequest::Get);
	}


	#[Override]
	protected function setUp(): void
	{
		$this->localeLinkGenerator->setLinks(['en_US' => self::SWITCHER_LINK]);
		$this->localeLinkGenerator->setLinksForLocaleParams([
			'cs_CZ' => 'https://www.rizek.test/skoleni/' . self::ACTION,
			'en_US' => 'https://www.burger.test/trainings/' . self::ACTION,
		]);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->localeLinkGenerator->reset();
		$this->session->getSection('training')->remove();
	}


	/**
	 * The materials are the attendee's, found with what their session on this host holds.
	 */
	public function testFilesNoAlternates(): void
	{
		$training = $this->session->getSection('training');
		$training->set('applicationId', self::APPLICATION_ID);
		$training->set('token', 's0m370k3n');
		$this->database->addFetchResult($this->getTrainingRow());
		$this->database->addFetchResult([
			'status' => 'ACCESS_TOKEN_USED',
			'trainingAction' => self::ACTION,
			'trainingStart' => new DateTime('2025-01-01 09:00:00'),
			'trainingEnd' => new DateTime('2025-01-01 17:00:00'),
		] + $this->dataFactory->getDatabaseResultData(self::APPLICATION_ID));
		$this->database->addFetchFieldResult(0); // Not a visitor blocked for spam
		$this->database->setFetchFieldDefaultResult(1); // For the status ids
		$this->database->addFetchPairsResult([]); // Children of the attended status
		$this->database->addFetchPairsResult([]); // Children of the canceled status
		$this->database->addFetchPairsResult([8 => 'MATERIALS_SENT']); // Parents of the access token used status
		$this->database->addFetchAllResult([
			[
				'added' => new DateTime('2025-01-02 10:00:00'),
				'fileId' => 1,
				'fileName' => 'file.zip',
				'start' => new DateTime('2025-01-01 09:00:00'),
			],
		]);
		$this->database->setFetchAllDefaultResult([ // The training's URLs, it has a page in both languages
			['trainingId' => 1, 'language' => 'cs_CZ', 'action' => self::ACTION],
			['trainingId' => 1, 'language' => 'en_US', 'action' => self::ACTION],
		]);
		$this->storage->setFilesDir(__DIR__ . '/../../../Training/Files/');
		$html = $this->render('files');
		Assert::contains('file.zip', $html);
		Assert::notContains('hreflang', $html);
		Assert::same([], $this->localeLinkGenerator->getLinksForLocaleParamsDestinations());
		Assert::contains('href="' . self::SWITCHER_LINK . '"', $html);
	}


	/**
	 * The page after applying shows the application the visitor's session on this host holds.
	 */
	public function testSuccessNoAlternates(): void
	{
		$dateId = 10;
		$this->session->getSection('training')->set('trainingId', $dateId);
		$this->database->addFetchFieldResult(0); // Not a visitor blocked for spam
		$this->database->addFetchResult($this->getTrainingRow());
		$this->database->addFetchAllResult([
			[
				'dateId' => $dateId,
				'trainingId' => 1,
				'action' => self::ACTION,
				'name' => 'PHP',
				'price' => 1000,
				'studentDiscount' => null,
				'hasCustomPrice' => 0,
				'hasCustomStudentDiscount' => 0,
				'start' => new DateTime('+1 week'),
				'end' => new DateTime('+1 week 2 days'),
				'labelJson' => null,
				'public' => 1,
				'status' => 'CONFIRMED',
				'remote' => 1,
				'remoteUrl' => null,
				'remoteNotes' => null,
				'venueId' => null,
				'venueAction' => null,
				'venueHref' => null,
				'venueName' => null,
				'venueNameExtended' => null,
				'venueAddress' => null,
				'venueDescription' => null,
				'venueCity' => null,
				'cooperationId' => null,
				'cooperationDescription' => null,
				'videoHref' => null,
				'feedbackHref' => null,
				'note' => null,
			],
		]);
		$html = $this->render('success');
		Assert::contains('messages.trainings.submitted.confirmed', $html);
		Assert::notContains('hreflang', $html);
		Assert::same([], $this->localeLinkGenerator->getLinksForLocaleParamsDestinations());
		Assert::contains('href="' . self::SWITCHER_LINK . '"', $html);
	}


	/**
	 * @return array<string, int|string|null>
	 */
	private function getTrainingRow(): array
	{
		return [
			'id' => 1,
			'action' => self::ACTION,
			'name' => 'PHP',
			'description' => 'Description',
			'content' => 'Content',
			'upsell' => null,
			'prerequisites' => null,
			'audience' => null,
			'capacity' => null,
			'price' => null,
			'studentDiscount' => null,
			'materials' => null,
			'custom' => 0,
			'successorId' => null,
			'discontinuedId' => null,
		];
	}


	private function render(string $action): string
	{
		$presenter = $this->applicationPresenter->createUiPresenter('Www:Trainings', 'Trainings', $action);
		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new Request('Www:Trainings', IRequest::Get, ['action' => $action, 'name' => self::ACTION]));
		assert($response instanceof TextResponse);
		$source = $response->getSource();
		assert(is_string($source) || $source instanceof Stringable);
		return (string)$source;
	}

}

TestCaseRunner::run(TrainingsPresenterTest::class);
