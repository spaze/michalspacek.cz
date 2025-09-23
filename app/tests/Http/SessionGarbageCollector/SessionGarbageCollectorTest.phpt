<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SessionGarbageCollector;

use DateTimeImmutable;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Database\Explorer;
use Override;
use RuntimeException;
use Spaze\Session\MysqlSessionHandler;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class SessionGarbageCollectorTest extends TestCase
{

	private const string LOG_TIME = '2020-10-05 13:37:37';
	private const string LOG_TIMEZONE = 'Indian/Reunion';


	public function __construct(
		private readonly Database $database,
		private readonly DateTimeMachineFactory $dateTimeFactory,
	) {
		$this->dateTimeFactory->setDateTime(new DateTimeImmutable(self::LOG_TIME . ' ' . self::LOG_TIMEZONE));
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testCleanSessionsFailure(): void
	{
		Assert::same(SessionGarbageCollectorReturnCode::GcFailure, $this->createSessionGarbageCollector(false, null)->cleanSessions());
		$expected = [
			'id_sessions_gc_log' => 1,
			'gc_time' => self::LOG_TIME,
			'gc_time_timezone' => self::LOG_TIMEZONE,
			'deleted' => null,
			'return_code' => SessionGarbageCollectorReturnCode::GcFailure,
			'message' => null,
		];
		Assert::same([$expected, $expected], $this->database->getParamsArrayForQuery('INSERT INTO sessions_gc_log'));
	}


	public function testCleanSessionsOk(): void
	{
		Assert::same(SessionGarbageCollectorReturnCode::Ok, $this->createSessionGarbageCollector(0, null)->cleanSessions());
		Assert::same(SessionGarbageCollectorReturnCode::Ok, $this->createSessionGarbageCollector(303, null)->cleanSessions());
		$expected0 = [
			'id_sessions_gc_log' => 1,
			'gc_time' => self::LOG_TIME,
			'gc_time_timezone' => self::LOG_TIMEZONE,
			'deleted' => 0,
			'return_code' => SessionGarbageCollectorReturnCode::Ok,
			'message' => null,
		];
		$expected303 = [
			'id_sessions_gc_log' => 1,
			'gc_time' => self::LOG_TIME,
			'gc_time_timezone' => self::LOG_TIMEZONE,
			'deleted' => 303,
			'return_code' => SessionGarbageCollectorReturnCode::Ok,
			'message' => null,
		];
		Assert::same([$expected0, $expected0, $expected303, $expected303], $this->database->getParamsArrayForQuery('INSERT INTO sessions_gc_log'));
	}


	public function testCleanSessionsException(): void
	{
		Assert::same(SessionGarbageCollectorReturnCode::Exception, $this->createSessionGarbageCollector(808, new RuntimeException("Trust me I'm engineer"))->cleanSessions());
		$expected = [
			'id_sessions_gc_log' => 1,
			'gc_time' => self::LOG_TIME,
			'gc_time_timezone' => self::LOG_TIMEZONE,
			'deleted' => null,
			'return_code' => SessionGarbageCollectorReturnCode::Exception,
			'message' => "Trust me I'm engineer",
		];
		Assert::same([$expected, $expected], $this->database->getParamsArrayForQuery('INSERT INTO sessions_gc_log'));
	}


	private function createSessionGarbageCollector(int|false $gcReturnValue, ?RuntimeException $willThrow): SessionGarbageCollector
	{
		$sessionHandler = new class ($this->database, $gcReturnValue, $willThrow) extends MysqlSessionHandler {

			public function __construct(
				Explorer $explorer,
				private readonly int|false $gcReturnValue,
				private readonly ?RuntimeException $willThrow,
			) {
				parent::__construct($explorer);
			}


			#[Override]
			public function gc(int $max_lifetime): int|false
			{
				if ($this->willThrow !== null) {
					throw $this->willThrow;
				}
				return $this->gcReturnValue;
			}

		};
		return new SessionGarbageCollector($sessionHandler, $this->database, $this->dateTimeFactory);
	}

}

TestCaseRunner::run(SessionGarbageCollectorTest::class);
