<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Session;

use DateTimeImmutable;
use MichalSpacekCz\GarbageCollector\GarbageCollectorLogger;
use MichalSpacekCz\GarbageCollector\GarbageCollectorReturnCode;
use MichalSpacekCz\GarbageCollector\GarbageCollectorType;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\Http\NullSession;
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
		private readonly NullSession $session,
	) {
		$this->dateTimeFactory->setDateTime(new DateTimeImmutable(self::LOG_TIME . ' ' . self::LOG_TIMEZONE));
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testCleanFailure(): void
	{
		Assert::same(GarbageCollectorReturnCode::Failure, $this->createSessionGarbageCollector(false, null)->clean());
		$params = $this->database->getParamsArrayForQuery('INSERT INTO gc_log');
		Assert::same(GarbageCollectorType::Sessions->value, $params[0]['gc_type']);
		Assert::same(self::LOG_TIME, $params[0]['gc_time']);
		Assert::same(self::LOG_TIMEZONE, $params[0]['gc_time_timezone']);
		Assert::same(null, $params[0]['deleted']);
		Assert::same(GarbageCollectorReturnCode::Failure->value, $params[0]['return_code']);
		assert(is_string($params[0]['message']));
		Assert::match('~::gc\(\) returned false$~', $params[0]['message']);
		Assert::same($params[0], $params[1]);
	}


	public function testCleanOk(): void
	{
		Assert::same(GarbageCollectorReturnCode::Ok, $this->createSessionGarbageCollector(0, null)->clean());
		Assert::same(GarbageCollectorReturnCode::Ok, $this->createSessionGarbageCollector(303, null)->clean());
		$expected0 = [
			'gc_type' => GarbageCollectorType::Sessions->value,
			'gc_time' => self::LOG_TIME,
			'gc_time_timezone' => self::LOG_TIMEZONE,
			'deleted' => 0,
			'return_code' => GarbageCollectorReturnCode::Ok->value,
			'message' => null,
		];
		$expected303 = [
			'gc_type' => GarbageCollectorType::Sessions->value,
			'gc_time' => self::LOG_TIME,
			'gc_time_timezone' => self::LOG_TIMEZONE,
			'deleted' => 303,
			'return_code' => GarbageCollectorReturnCode::Ok->value,
			'message' => null,
		];
		Assert::same([$expected0, $expected0, $expected303, $expected303], $this->database->getParamsArrayForQuery('INSERT INTO gc_log'));
	}


	public function testCleanException(): void
	{
		Assert::same(GarbageCollectorReturnCode::Failure, $this->createSessionGarbageCollector(808, new RuntimeException("Trust me I'm engineer"))->clean());
		$expected = [
			'gc_type' => GarbageCollectorType::Sessions->value,
			'gc_time' => self::LOG_TIME,
			'gc_time_timezone' => self::LOG_TIMEZONE,
			'deleted' => null,
			'return_code' => GarbageCollectorReturnCode::Failure->value,
			'message' => "Trust me I'm engineer",
		];
		Assert::same([$expected, $expected], $this->database->getParamsArrayForQuery('INSERT INTO gc_log'));
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
		return new SessionGarbageCollector($sessionHandler, new GarbageCollectorLogger($this->database, $this->dateTimeFactory), $this->session);
	}

}

TestCaseRunner::run(SessionGarbageCollectorTest::class);
