<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Files;

use DateTime;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\Mails\TrainingMailMessageFactory;
use MichalSpacekCz\Training\Statuses;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingFilesSessionSectionTest extends TestCase
{

	private const APPLICATION_ID = 303;
	private const APPLICATION_ID_KEY = 'applicationId';
	private const TOKEN = 'AToken';
	private const TOKEN_KEY = 'token';

	private TrainingFilesSessionSection $trainingFilesSessionSection;
	private SessionSection $sessionSection;


	public function __construct(
		private readonly Session $sessionHandler,
		private readonly Statuses $trainingStatuses,
		private readonly TrainingMailMessageFactory $trainingMailMessageFactory,
		private readonly TrainingFiles $trainingFiles,
	) {
	}


	protected function setUp(): void
	{
		$trainingFilesSessionSection = $this->sessionHandler->getSection('training', TrainingFilesSessionSection::class);
		if (!$trainingFilesSessionSection instanceof TrainingFilesSessionSection) {
			throw new ShouldNotHappenException();
		}
		$this->trainingFilesSessionSection = $trainingFilesSessionSection;
		$this->sessionSection = $this->sessionHandler->getSection('training');
	}


	protected function tearDown(): void
	{
		$this->sessionSection->remove();
	}


	public function testSetValues(): void
	{
		$this->trainingFilesSessionSection->setValues(self::TOKEN, null);
		Assert::same(self::TOKEN, $this->sessionSection->get(self::TOKEN_KEY));
		Assert::null($this->sessionSection->get(self::APPLICATION_ID_KEY));

		$this->trainingFilesSessionSection->setValues(self::TOKEN, $this->buildApplication());
		Assert::same(self::TOKEN, $this->sessionSection->get(self::TOKEN_KEY));
		Assert::same(self::APPLICATION_ID, $this->sessionSection->get(self::APPLICATION_ID_KEY));
	}


	public function testIsComplete(): void
	{
		Assert::false($this->trainingFilesSessionSection->isComplete());

		$this->sessionSection->set(self::APPLICATION_ID_KEY, self::APPLICATION_ID);
		Assert::false($this->trainingFilesSessionSection->isComplete());

		$this->sessionSection->remove(self::APPLICATION_ID_KEY);
		$this->sessionSection->set(self::TOKEN_KEY, self::TOKEN);
		Assert::false($this->trainingFilesSessionSection->isComplete());

		$this->sessionSection->set(self::APPLICATION_ID_KEY, self::APPLICATION_ID);
		$this->sessionSection->set(self::TOKEN_KEY, self::TOKEN);
		Assert::true($this->trainingFilesSessionSection->isComplete());
	}


	public function testGetApplicationId(): void
	{
		$this->sessionSection->set(self::APPLICATION_ID_KEY, "I'm a teapot");
		Assert::exception(function (): void {
			$this->trainingFilesSessionSection->getApplicationId();
		}, ShouldNotHappenException::class, "Session key applicationId type should be int, but it's a string");

		$this->sessionSection->set(self::APPLICATION_ID_KEY, self::APPLICATION_ID);
		Assert::same(self::APPLICATION_ID, $this->trainingFilesSessionSection->getApplicationId());
	}


	public function testGetToken(): void
	{
		$this->sessionSection->set(self::TOKEN_KEY, 418);
		Assert::exception(function (): void {
			$this->trainingFilesSessionSection->getToken();
		}, ShouldNotHappenException::class, "Session key token type should be string, but it's a int");

		$this->sessionSection->set(self::TOKEN_KEY, self::TOKEN);
		Assert::same(self::TOKEN, $this->trainingFilesSessionSection->getToken());
	}


	private function buildApplication(): TrainingApplication
	{
		return new TrainingApplication(
			$this->trainingStatuses,
			$this->trainingMailMessageFactory,
			$this->trainingFiles,
			self::APPLICATION_ID,
			null,
			null,
			false,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			'ATTENDED',
			new DateTime(),
			true,
			false,
			false,
			null,
			null,
			'action',
			Html::fromText('Name'),
			null,
			null,
			false,
			false,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			'',
			'',
			null,
			null,
			null,
			'accessToken',
			'michal-spacek',
			'Michal Špaček',
			'MŠ',
		);
	}

}

$runner->run(TrainingFilesSessionSectionTest::class);
