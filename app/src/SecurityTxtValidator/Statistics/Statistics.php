<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Statistics;

use MichalSpacekCz\DateTime\DateTimeFactoryUtc;
use Nette\Database\Explorer;
use Throwable;
use Tracy\Debugger;

/**
 * One counter per UTC day, metric and bucket, counted up in place, so the table grows by a few rows a day whatever
 * the traffic and never holds a host name.
 *
 * A counter that cannot be written is logged and let go: the visitor came for a result, and a count of results is not
 * worth taking it away from them.
 */
final readonly class Statistics
{

	public function __construct(
		private Explorer $database,
		private DateTimeFactoryUtc $dateTimeFactory,
	) {
	}


	public function increment(StatisticsBucket ...$buckets): void
	{
		if ($buckets === []) {
			return;
		}
		try {
			$day = $this->dateTimeFactory->getNow()->format('Y-m-d');
			$rows = [];
			foreach ($buckets as $bucket) {
				$rows[] = [
					'day' => $day,
					'metric' => $bucket->getMetric()->value,
					'bucket' => $bucket->getBucket(),
					'count' => 1,
				];
			}
			$this->database->query('INSERT INTO statistics', $rows, 'ON DUPLICATE KEY UPDATE count = count + 1');
		} catch (Throwable $e) {
			Debugger::log($e, Debugger::EXCEPTION);
		}
	}

}
