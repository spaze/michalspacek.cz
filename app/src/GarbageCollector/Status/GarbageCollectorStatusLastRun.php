<?php
declare(strict_types = 1);

namespace MichalSpacekCz\GarbageCollector\Status;

use DateTimeImmutable;

final readonly class GarbageCollectorStatusLastRun
{

	public function __construct(
		private(set) DateTimeImmutable $date,
		private(set) bool $tooOld,
	) {
	}

}
