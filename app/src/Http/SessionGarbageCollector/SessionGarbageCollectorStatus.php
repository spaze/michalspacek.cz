<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SessionGarbageCollector;

use DateTime;

final readonly class SessionGarbageCollectorStatus
{

	public function __construct(
		public ?bool $ok = null,
		public ?DateTime $gcTime = null,
		public ?bool $noStatus = null,
		public ?int $multipleStatuses = null,
		public ?int $daysOld = null,
		public ?string $message = null,
	) {
	}

}
