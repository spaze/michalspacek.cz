<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\GarbageCollector;

use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class GarbageCollectorRunnerTest extends TestCase
{

	private string $lockFilePath = '';


	public function __construct(
		// Injected so that the DI container fully initialises Tracy's logger; the runner's Debugger::log otherwise throws.
		private readonly NullLogger $nullLogger,
	) {
	}


	#[Override]
	protected function setUp(): void
	{
		// Real file needed, flock() doesn't work with Tester\FileMock
		$this->lockFilePath = sys_get_temp_dir() . '/garbage-collector-runner-test-' . uniqid() . '.lock';
	}


	#[Override]
	protected function tearDown(): void
	{
		if (file_exists($this->lockFilePath)) {
			unlink($this->lockFilePath);
		}
	}


	public function testRunAllGcsSuccessReturnsZero(): void
	{
		$runner = $this->createRunner([
			$this->createGc(GarbageCollectorType::Sessions, GarbageCollectorReturnCode::Ok),
			$this->createGc(GarbageCollectorType::AuthTokens, GarbageCollectorReturnCode::Ok),
		]);
		Assert::same(0, $runner->run());
	}


	public function testRunAnyGcFailureReturnsNonZero(): void
	{
		$runner = $this->createRunner([
			$this->createGc(GarbageCollectorType::Sessions, GarbageCollectorReturnCode::Ok),
			$this->createGc(GarbageCollectorType::AuthTokens, GarbageCollectorReturnCode::Failure),
		]);
		Assert::notSame(0, $runner->run());
	}


	public function testRunGcThrowingReturnsNonZeroAndLogsException(): void
	{
		$runner = $this->createRunner([
			$this->createThrowingGc(GarbageCollectorType::Sessions, new RuntimeException('boom')),
		]);
		Assert::notSame(0, $runner->run());
		Assert::count(1, $this->nullLogger->getLogged());
	}


	public function testRunWhenLockContentionReturnsZero(): void
	{
		// Acquire the lock externally first
		$externalLock = fopen($this->lockFilePath, 'c');
		if ($externalLock === false) {
			Assert::fail('Could not open lock file');
			return;
		}
		Assert::true(flock($externalLock, LOCK_EX | LOCK_NB));

		$runner = $this->createRunner([
			$this->createGc(GarbageCollectorType::Sessions, GarbageCollectorReturnCode::Ok),
		]);
		Assert::same(0, $runner->run());

		flock($externalLock, LOCK_UN);
		fclose($externalLock);
	}


	/**
	 * @param list<GarbageCollector> $gcs
	 */
	private function createRunner(array $gcs): GarbageCollectorRunner
	{
		return new GarbageCollectorRunner($this->lockFilePath, new GarbageCollectors($gcs));
	}


	private function createGc(GarbageCollectorType $type, GarbageCollectorReturnCode $returns): GarbageCollector
	{
		return new class ($type, $returns) implements GarbageCollector {

			public function __construct(
				private readonly GarbageCollectorType $type,
				private readonly GarbageCollectorReturnCode $returns,
			) {
			}


			#[Override]
			public function getGarbageCollectorType(): GarbageCollectorType
			{
				return $this->type;
			}


			#[Override]
			public function getIntervalSeconds(): int
			{
				return 24 * 60 * 60;
			}


			#[Override]
			public function clean(): GarbageCollectorReturnCode
			{
				return $this->returns;
			}

		};
	}


	private function createThrowingGc(GarbageCollectorType $type, RuntimeException $error): GarbageCollector
	{
		return new class ($type, $error) implements GarbageCollector {

			public function __construct(
				private readonly GarbageCollectorType $type,
				private readonly RuntimeException $error,
			) {
			}


			#[Override]
			public function getGarbageCollectorType(): GarbageCollectorType
			{
				return $this->type;
			}


			#[Override]
			public function getIntervalSeconds(): int
			{
				return 24 * 60 * 60;
			}


			#[Override]
			public function clean(): GarbageCollectorReturnCode
			{
				throw $this->error;
			}

		};
	}

}

TestCaseRunner::run(GarbageCollectorRunnerTest::class);
