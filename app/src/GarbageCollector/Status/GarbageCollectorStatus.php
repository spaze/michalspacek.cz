<?php
declare(strict_types = 1);

namespace MichalSpacekCz\GarbageCollector\Status;

use DateTimeImmutable;
use MichalSpacekCz\GarbageCollector\GarbageCollectorType;

final readonly class GarbageCollectorStatus
{

	public function __construct(
		private(set) GarbageCollectorType $type,
		private(set) bool $ok,
		private(set) bool $noStatus = false,
		private(set) bool $tooOld = false,
		private(set) ?DateTimeImmutable $lastRun = null,
		private(set) ?string $message = null,
	) {
	}

}
