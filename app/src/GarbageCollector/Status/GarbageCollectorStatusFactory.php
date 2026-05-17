<?php
declare(strict_types = 1);

namespace MichalSpacekCz\GarbageCollector\Status;

use DateTimeInterface;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\GarbageCollector\GarbageCollectorReturnCode;
use MichalSpacekCz\GarbageCollector\GarbageCollectors;
use Nette\Database\Explorer;

final readonly class GarbageCollectorStatusFactory
{

	public function __construct(
		private GarbageCollectors $garbageCollectors,
		private Explorer $database,
		private DateTimeFactory $dateTimeFactory,
	) {
	}


	/**
	 * @return list<GarbageCollectorStatus>
	 */
	public function createStatuses(): array
	{
		$statuses = [];
		$now = $this->dateTimeFactory->create();
		foreach ($this->garbageCollectors->getAll() as $gc) {
			$type = $gc->getGarbageCollectorType();
			$row = $this->database->fetch(
				'SELECT
					gc_time AS gcTime,
					gc_time_timezone AS gcTimeTimezone,
					return_code AS returnCode,
					message
				FROM gc_log
				WHERE gc_type = ?
				ORDER BY gc_time DESC
				LIMIT 1',
				$type->value,
			);
			if ($row === null) {
				$statuses[] = new GarbageCollectorStatus($type, false, true);
				continue;
			}
			assert($row->gcTime instanceof DateTimeInterface);
			assert(is_string($row->gcTimeTimezone));
			assert(is_int($row->returnCode));
			assert($row->message === null || is_string($row->message));
			$lastRun = $this->dateTimeFactory->createFrom($row->gcTime, $row->gcTimeTimezone);
			$code = GarbageCollectorReturnCode::tryFrom($row->returnCode);
			$tooOld = $lastRun->getTimestamp() < $now->getTimestamp() - 2 * $gc->getIntervalSeconds(); // 2x grace period for cron jitter or temporary errors
			$message = $code === null
				? sprintf('Unknown return code %d%s', $row->returnCode, $row->message !== null ? " ({$row->message})" : '')
				: $row->message;
			$ok = $code === GarbageCollectorReturnCode::Ok && !$tooOld && $message === null;
			$statuses[] = new GarbageCollectorStatus(
				$type,
				$ok,
				false,
				$tooOld,
				$lastRun,
				$message,
			);
		}
		return $statuses;
	}

}
