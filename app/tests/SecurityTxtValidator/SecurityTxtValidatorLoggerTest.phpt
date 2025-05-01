<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use LogicException;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtValidatorLogger;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class SecurityTxtValidatorLoggerTest extends TestCase
{

	public function __construct(
		private readonly SecurityTxtValidatorLogger $validatorLogger,
		private readonly NullLogger $nullLogger,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->nullLogger->reset();
	}


	public function testLog(): void
	{
		$this->validatorLogger->log('host.example', 'log message');
		Assert::same($this->nullLogger->getLogged(), ['host.example: log message']);
	}


	public function testLogException(): void
	{
		$this->validatorLogger->logException('host.example', new RuntimeException('Runtime error', previous: new LogicException('Logic exception')));
		Assert::same($this->nullLogger->getLogged(), ['host.example: Runtime error (previous: Logic exception)']);
	}

}

TestCaseRunner::run(SecurityTxtValidatorLoggerTest::class);
