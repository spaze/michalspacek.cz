<?php
declare(strict_types = 1);

namespace MichalSpacekCz\GarbageCollector;

use MichalSpacekCz\DateTime\DateTimeFactory;
use Nette\Database\Explorer;

final readonly class GarbageCollectorLogger
{

	public function __construct(
		private Explorer $database,
		private DateTimeFactory $dateTimeFactory,
	) {
	}


	public function log(
		GarbageCollectorType $type,
		GarbageCollectorReturnCode $code,
		?int $deleted,
		?string $message,
	): void {
		$datetime = $this->dateTimeFactory->create();
		$data = [
			'gc_type' => $type->value,
			'gc_time' => $datetime,
			'gc_time_timezone' => $datetime->getTimezone()->getName(),
			'deleted' => $deleted,
			'return_code' => $code->value,
			'message' => $message,
		];
		$this->database->query('INSERT INTO gc_log', $data, 'ON DUPLICATE KEY UPDATE', $data);
	}

}
