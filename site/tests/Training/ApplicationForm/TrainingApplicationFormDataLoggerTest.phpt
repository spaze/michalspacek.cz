<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use DateTime;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\Http\NullSession;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\ApplicationForm\TrainingApplicationFormDataLogger;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\Applications\TrainingApplicationSessionSection;
use MichalSpacekCz\Training\Files\TrainingFiles;
use MichalSpacekCz\Training\Mails\TrainingMailMessageFactory;
use Nette\Utils\Html;
use Override;
use stdClass;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingApplicationFormDataLoggerTest extends TestCase
{

	private const APPLICATION_ID = 10;
	private const DATE_ID = 20;


	public function __construct(
		private readonly TrainingApplicationFormDataLogger $formDataLogger,
		private readonly NullLogger $logger,
		private readonly NullSession $session,
		private readonly Statuses $trainingStatuses,
		private readonly TrainingMailMessageFactory $trainingMailMessageFactory,
		private readonly TrainingFiles $trainingFiles,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->session->destroy();
		$this->logger->reset();
	}


	public function testLogNoValuesNoSession(): void
	{
		$this->formDataLogger->log(new stdClass(), 'foo', self::DATE_ID, null);
		Assert::same(['Application session data for foo: undefined, form values: empty'], $this->logger->getLogged());
	}


	public function testLogNoSession(): void
	{
		$values = new stdClass();
		$values->key1 = 'value1';
		$values->key2 = 'value2';
		$this->formDataLogger->log($values, 'foo', self::DATE_ID, null);
		Assert::same(["Application session data for foo: undefined, form values: key1 => 'value1', key2 => 'value2'"], $this->logger->getLogged());
	}


	public function testLogEmptySession(): void
	{
		$values = new stdClass();
		$values->key1 = 'value1';
		$values->key2 = 'value2';
		$this->formDataLogger->log($values, 'foo', self::DATE_ID, $this->getTrainingSessionSection());
		Assert::same(["Application session data for foo: empty, form values: key1 => 'value1', key2 => 'value2'"], $this->logger->getLogged());
	}


	public function testLog(): void
	{
		$values = new stdClass();
		$values->key1 = 'value1';
		$values->key2 = 'value2';
		$trainingName = 'foo';

		$session = $this->getTrainingSessionSection();
		$session->setApplicationForTraining($trainingName, $this->getApplication());
		$this->formDataLogger->log($values, $trainingName, self::DATE_ID, $session);
		$expected = sprintf("Application session data for foo: id => '%s', dateId => '%s', form values: key1 => 'value1', key2 => 'value2'", self::APPLICATION_ID, self::DATE_ID);
		Assert::same([$expected], $this->logger->getLogged());
	}


	private function getApplication(): TrainingApplication
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
			self::DATE_ID,
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


	private function getTrainingSessionSection(): TrainingApplicationSessionSection
	{
		$session = $this->session->getSection('section', TrainingApplicationSessionSection::class);
		if (!$session instanceof TrainingApplicationSessionSection) {
			throw new ShouldNotHappenException(sprintf('Session section type is %s, but should be %s', get_debug_type($session), TrainingApplicationSessionSection::class));
		}
		return $session;
	}

}

TestCaseRunner::run(TrainingApplicationFormDataLoggerTest::class);
