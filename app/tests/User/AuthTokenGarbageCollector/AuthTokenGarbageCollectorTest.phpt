<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\AuthTokenGarbageCollector;

use DateTimeImmutable;
use MichalSpacekCz\GarbageCollector\GarbageCollectorLogger;
use MichalSpacekCz\GarbageCollector\GarbageCollectorReturnCode;
use MichalSpacekCz\GarbageCollector\GarbageCollectorType;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\AuthTokens\UserAuthTokenLifetime;
use MichalSpacekCz\User\AuthTokens\UserAuthTokenType;
use Override;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class AuthTokenGarbageCollectorTest extends TestCase
{

	private const string NOW = '2026-05-17 12:00:00';
	private const string TIMEZONE = 'UTC';


	public function __construct(
		private readonly Database $database,
		private readonly DateTimeMachineFactory $dateTimeFactory,
	) {
		$this->dateTimeFactory->setDateTime(new DateTimeImmutable(self::NOW . ' ' . self::TIMEZONE));
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testCleanSumsDeletedAndLogsOk(): void
	{
		$lifetimeA = $this->createLifetime(UserAuthTokenType::PermanentLogin, 3);
		$lifetimeB = $this->createLifetime(UserAuthTokenType::AdminPasskeyReset, 5);
		$gc = new AuthTokenGarbageCollector(
			[$lifetimeA, $lifetimeB],
			new GarbageCollectorLogger($this->database, $this->dateTimeFactory),
		);
		Assert::same(GarbageCollectorReturnCode::Ok, $gc->clean());

		$expectedLog = [
			'gc_type' => GarbageCollectorType::AuthTokens->value,
			'gc_time' => self::NOW,
			'gc_time_timezone' => self::TIMEZONE,
			'deleted' => 8, // 3 + 5
			'return_code' => GarbageCollectorReturnCode::Ok->value,
			'message' => null,
		];
		Assert::same([$expectedLog, $expectedLog], $this->database->getParamsArrayForQuery('INSERT INTO gc_log'));
	}


	public function testCleanPartialFailureLogsAggregatedMessage(): void
	{
		$lifetimeA = $this->createLifetime(UserAuthTokenType::PermanentLogin, 2);
		$lifetimeB = $this->createFailingLifetime(UserAuthTokenType::AdminPasskeyReset, new RuntimeException('boom'));
		$gc = new AuthTokenGarbageCollector(
			[$lifetimeA, $lifetimeB],
			new GarbageCollectorLogger($this->database, $this->dateTimeFactory),
		);
		Assert::same(GarbageCollectorReturnCode::Failure, $gc->clean());

		$logs = $this->database->getParamsArrayForQuery('INSERT INTO gc_log');
		Assert::count(2, $logs);
		Assert::same(GarbageCollectorReturnCode::Failure->value, $logs[0]['return_code']);
		Assert::same(2, $logs[0]['deleted']);
		$message = $logs[0]['message'];
		assert(is_string($message));
		Assert::contains('AdminPasskeyReset: boom', $message);
	}


	public function testCleanNoLifetimesLogsOkZero(): void
	{
		$gc = new AuthTokenGarbageCollector(
			[],
			new GarbageCollectorLogger($this->database, $this->dateTimeFactory),
		);
		Assert::same(GarbageCollectorReturnCode::Ok, $gc->clean());

		$logs = $this->database->getParamsArrayForQuery('INSERT INTO gc_log');
		Assert::same(0, $logs[0]['deleted']);
	}


	private function createLifetime(UserAuthTokenType $type, int $deleteCount): UserAuthTokenLifetime
	{
		return new class ($type, $deleteCount) implements UserAuthTokenLifetime {

			public function __construct(
				private readonly UserAuthTokenType $type,
				private readonly int $deleteCount,
			) {
			}


			#[Override]
			public function getTokenType(): UserAuthTokenType
			{
				return $this->type;
			}


			#[Override]
			public function getTtl(): string
			{
				return '1 day';
			}


			#[Override]
			public function deleteExpired(): int
			{
				return $this->deleteCount;
			}

		};
	}


	private function createFailingLifetime(UserAuthTokenType $type, RuntimeException $error): UserAuthTokenLifetime
	{
		return new class ($type, $error) implements UserAuthTokenLifetime {

			public function __construct(
				private readonly UserAuthTokenType $type,
				private readonly RuntimeException $error,
			) {
			}


			#[Override]
			public function getTokenType(): UserAuthTokenType
			{
				return $this->type;
			}


			#[Override]
			public function getTtl(): string
			{
				return '1 day';
			}


			#[Override]
			public function deleteExpired(): int
			{
				throw $this->error;
			}

		};
	}

}

TestCaseRunner::run(AuthTokenGarbageCollectorTest::class);
