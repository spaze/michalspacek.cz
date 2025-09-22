<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace Http\Session;

use DateTime;
use MichalSpacekCz\Http\Session\SessionGarbageCollectorReturnCode;
use MichalSpacekCz\Http\Session\SessionGarbageCollectorStatusFactory;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class SessionGarbageCollectorStatusFactoryTest extends TestCase
{

	public function __construct(
		private readonly SessionGarbageCollectorStatusFactory $sessionGarbageCollectorStatusFactory,
		private readonly Database $database,
	) {
	}


	public function testCreateStatusNoStatus(): void
	{
		$this->database->setFetchAllDefaultResult([]);
		$status = $this->sessionGarbageCollectorStatusFactory->createStatus();
		Assert::false($status->ok);
		Assert::true($status->noStatus);
	}


	public function testCreateStatusMultipleStatuses(): void
	{
		$this->database->setFetchAllDefaultResult([[], []]);
		$status = $this->sessionGarbageCollectorStatusFactory->createStatus();
		Assert::false($status->ok);
		Assert::same(2, $status->multipleStatuses);
	}


	public function testCreateStatusDaysOld(): void
	{
		$time = new DateTime('-3 days');
		$this->database->setFetchAllDefaultResult([[
			'gcTime' => $time,
			'gcTimeTimezone' => 'Europe/Prague',
			'returnCode' => SessionGarbageCollectorReturnCode::Ok->value,
			'message' => null,
		]]);
		$status = $this->sessionGarbageCollectorStatusFactory->createStatus();
		Assert::false($status->ok);
		Assert::same($time, $status->gcTime);
		Assert::same(3, $status->daysOld);
		Assert::null($status->message);
	}


	public function testCreateStatusDaysOldWithMessage(): void
	{
		$time = new DateTime('-3 days');
		$this->database->setFetchAllDefaultResult([[
			'gcTime' => $time,
			'gcTimeTimezone' => 'Europe/Prague',
			'returnCode' => SessionGarbageCollectorReturnCode::Ok->value,
			'message' => 'foo',
		]]);
		$status = $this->sessionGarbageCollectorStatusFactory->createStatus();
		Assert::false($status->ok);
		Assert::same($time, $status->gcTime);
		Assert::same(3, $status->daysOld);
		Assert::same('foo', $status->message);
	}


	public function testCreateStatusGcFailure(): void
	{
		$time = new DateTime('-15 hours');
		$this->database->setFetchAllDefaultResult([[
			'gcTime' => $time,
			'gcTimeTimezone' => 'Europe/Prague',
			'returnCode' => SessionGarbageCollectorReturnCode::GcFailure->value,
			'message' => null,
		]]);
		$status = $this->sessionGarbageCollectorStatusFactory->createStatus();
		Assert::false($status->ok);
		Assert::same($time, $status->gcTime);
		Assert::null($status->daysOld);
		Assert::null($status->message);
	}


	public function testCreateStatusException(): void
	{
		$time = new DateTime('-15 hours');
		$this->database->setFetchAllDefaultResult([[
			'gcTime' => $time,
			'gcTimeTimezone' => 'Europe/Prague',
			'returnCode' => SessionGarbageCollectorReturnCode::Exception->value,
			'message' => 'exception',
		]]);
		$status = $this->sessionGarbageCollectorStatusFactory->createStatus();
		Assert::false($status->ok);
		Assert::same($time, $status->gcTime);
		Assert::null($status->daysOld);
		Assert::same('exception', $status->message);
	}


	public function testCreateStatusUnknownReturnCode(): void
	{
		$time = new DateTime('-15 hours');
		$this->database->setFetchAllDefaultResult([[
			'gcTime' => $time,
			'gcTimeTimezone' => 'Europe/Prague',
			'returnCode' => -1,
			'message' => 'previous',
		]]);
		$status = $this->sessionGarbageCollectorStatusFactory->createStatus();
		Assert::false($status->ok);
		Assert::same($time, $status->gcTime);
		Assert::null($status->daysOld);
		Assert::same('Unknown return code -1 (previous)', $status->message);
	}


	public function testCreateStatus(): void
	{
		$time = new DateTime('-15 hours');
		$this->database->setFetchAllDefaultResult([[
			'gcTime' => $time,
			'gcTimeTimezone' => 'Europe/Prague',
			'returnCode' => SessionGarbageCollectorReturnCode::Ok->value,
			'message' => null,
		]]);
		$status = $this->sessionGarbageCollectorStatusFactory->createStatus();
		Assert::true($status->ok);
		Assert::same($time, $status->gcTime);
		Assert::null($status->daysOld);
		Assert::null($status->message);
	}

}

TestCaseRunner::run(SessionGarbageCollectorStatusFactoryTest::class);
