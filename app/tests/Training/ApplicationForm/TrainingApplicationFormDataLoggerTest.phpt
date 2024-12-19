<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationForm;

use MichalSpacekCz\Test\Http\NullSession;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\Training\TrainingTestDataFactory;
use MichalSpacekCz\Training\Applications\TrainingApplicationSessionSection;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingApplicationFormDataLoggerTest extends TestCase
{

	private const int APPLICATION_ID = 10;
	private const int DATE_ID = 20;


	public function __construct(
		private readonly TrainingApplicationFormDataLogger $formDataLogger,
		private readonly NullLogger $logger,
		private readonly NullSession $session,
		private readonly TrainingTestDataFactory $dataFactory,
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
		$this->formDataLogger->log([], 'foo', self::DATE_ID, null);
		Assert::same(['Application session data for foo: undefined, form values: empty'], $this->logger->getLogged());
	}


	public function testLogNoSession(): void
	{
		$values = [
			'key1' => 'value1',
			'key2' => 'value2',
		];
		$this->formDataLogger->log($values, 'foo', self::DATE_ID, null);
		Assert::same(["Application session data for foo: undefined, form values: key1 => 'value1', key2 => 'value2'"], $this->logger->getLogged());
	}


	public function testLogEmptySession(): void
	{
		$values = [
			'key1' => 'value1',
			'key2' => 'value2',
		];
		$this->formDataLogger->log($values, 'foo', self::DATE_ID, $this->getTrainingSessionSection());
		Assert::same(["Application session data for foo: empty, form values: key1 => 'value1', key2 => 'value2'"], $this->logger->getLogged());
	}


	public function testLog(): void
	{
		$values = [
			'key1' => 'value1',
			'key2' => 'value2',
			'key3' => 1336,
		];
		$trainingName = 'foo';

		$session = $this->getTrainingSessionSection();
		$session->setApplicationForTraining($trainingName, $this->dataFactory->getTrainingApplication(self::APPLICATION_ID, dateId: self::DATE_ID));
		$this->formDataLogger->log($values, $trainingName, self::DATE_ID, $session);
		$expected = sprintf("Application session data for foo: id => '%s', dateId => '%s', form values: key1 => 'value1', key2 => 'value2', key3 => int", self::APPLICATION_ID, self::DATE_ID);
		Assert::same([$expected], $this->logger->getLogged());
	}


	private function getTrainingSessionSection(): TrainingApplicationSessionSection
	{
		return $this->session->getSection('section', TrainingApplicationSessionSection::class);
	}

}

TestCaseRunner::run(TrainingApplicationFormDataLoggerTest::class);
