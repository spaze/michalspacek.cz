<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Test\Http\NullSession;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Training\ApplicationForm\TrainingApplicationFormDataLogger;
use stdClass;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingApplicationFormDataLoggerTest extends TestCase
{

	public function __construct(
		private readonly TrainingApplicationFormDataLogger $formDataLogger,
		private readonly NullLogger $logger,
		private readonly NullSession $session,
	) {
	}


	protected function tearDown(): void
	{
		$this->session->destroy();
		$this->logger->reset();
	}


	public function testLogNoValuesNoSession(): void
	{
		$this->formDataLogger->log(new stdClass(), 'foo', null);
		Assert::same(['Application session data for foo: undefined, form values: empty'], $this->logger->getLogged());
	}


	public function testLogNoSession(): void
	{
		$values = new stdClass();
		$values->key1 = 'value1';
		$values->key2 = 'value2';
		$this->formDataLogger->log($values, 'foo', null);
		Assert::same(['Application session data for foo: undefined, form values: key1 => "value1", key2 => "value2"'], $this->logger->getLogged());
	}


	public function testLogEmptySession(): void
	{
		$values = new stdClass();
		$values->key1 = 'value1';
		$values->key2 = 'value2';
		$this->formDataLogger->log($values, 'foo', $this->session->getSection('section'));
		Assert::same(['Application session data for foo: empty, form values: key1 => "value1", key2 => "value2"'], $this->logger->getLogged());
	}


	public function testLog(): void
	{
		$values = new stdClass();
		$values->key1 = 'value1';
		$values->key2 = 'value2';
		$trainingName = 'foo';

		$session = $this->session->getSection('section');
		$session->set('application', [
			$trainingName => [
				'session1' => 'sess1',
				'session2' => 'sess2',
			],
		]);
		$this->formDataLogger->log($values, $trainingName, $session);
		Assert::same(['Application session data for foo: session1 => "sess1", session2 => "sess2", form values: key1 => "value1", key2 => "value2"'], $this->logger->getLogged());
	}

}

$runner->run(TrainingApplicationFormDataLoggerTest::class);
