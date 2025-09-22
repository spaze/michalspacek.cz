<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Session;

use DateTime;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\DateTime\DateTimeZoneFactory;

final readonly class SessionGarbageCollectorStatusFactory
{

	public function __construct(
		private TypedDatabase $typedDatabase,
		private DateTimeFactory $dateTimeFactory,
		private DateTimeZoneFactory $dateTimeZoneFactory,
	) {
	}


	public function createStatus(): SessionGarbageCollectorStatus
	{
		$rows = $this->typedDatabase->fetchAll(
			'SELECT
				gc_time AS gcTime,
				gc_time_timezone AS gcTimeTimezone,
				return_code AS returnCode,
				message
			FROM sessions_gc_log',
		);
		$count = count($rows);
		if ($count === 0) {
			return new SessionGarbageCollectorStatus(ok: false, noStatus: true);
		}
		if ($count > 1) {
			return new SessionGarbageCollectorStatus(ok: false, multipleStatuses: $count);
		}
		$row = $rows[0];
		assert($row->gcTime instanceof DateTime);
		assert(is_string($row->gcTimeTimezone));
		assert(is_int($row->returnCode));
		assert($row->message === null || is_string($row->message));
		$row->gcTime->setTimezone($this->dateTimeZoneFactory->get($row->gcTimeTimezone));
		$returnCode = SessionGarbageCollectorReturnCode::tryFrom($row->returnCode);
		if ($returnCode === null) {
			return new SessionGarbageCollectorStatus(false, gcTime: $row->gcTime, message: "Unknown return code {$row->returnCode} ({$row->message})");
		}
		$now = $this->dateTimeFactory->create();
		$days = $now->diff($row->gcTime)->days;
		$tooOld = $days > 1;
		if ($tooOld || $row->message !== null) {
			return new SessionGarbageCollectorStatus(ok: false, gcTime: $row->gcTime, daysOld: $tooOld ? $days : null, message: $row->message);
		}
		return new SessionGarbageCollectorStatus($returnCode === SessionGarbageCollectorReturnCode::Ok, gcTime: $row->gcTime);
	}

}
