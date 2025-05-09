<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationStatuses;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\DateTime\DateTimeZoneFactory;
use MichalSpacekCz\DateTime\Exceptions\InvalidTimezoneException;
use Nette\Database\Explorer;
use Tracy\Debugger;

final class TrainingApplicationStatusHistory
{

	/** @var array<int, list<TrainingApplicationStatusHistoryItem>> */
	private array $statusHistory = [];


	public function __construct(
		private readonly Explorer $database,
		private readonly TypedDatabase $typedDatabase,
		private readonly DateTimeZoneFactory $dateTimeZoneFactory,
	) {
	}


	/**
	 * @return list<TrainingApplicationStatusHistoryItem>
	 * @throws InvalidTimezoneException
	 */
	public function getStatusHistory(int $applicationId): array
	{
		if (!isset($this->statusHistory[$applicationId])) {
			$rows = $this->typedDatabase->fetchAll(
				'SELECT
					h.id_status_log AS id,
					h.key_status AS statusId,
					s.status,
					h.status_time AS statusTime,
					h.status_time_timezone AS statusTimeTimeZone
				FROM training_application_status s
					JOIN training_application_status_history h ON h.key_status = s.id_status
				WHERE h.key_application = ?
				ORDER BY h.status_time DESC, h.key_status DESC',
				$applicationId,
			);
			$items = [];
			foreach ($rows as $row) {
				assert(is_int($row->id));
				assert(is_int($row->statusId));
				assert(is_string($row->status));
				assert($row->statusTime instanceof DateTime);
				assert(is_string($row->statusTimeTimeZone));
				$items[] = new TrainingApplicationStatusHistoryItem(
					$row->id,
					$row->statusId,
					TrainingApplicationStatus::from($row->status),
					DateTimeImmutable::createFromMutable($row->statusTime)->setTimezone($this->dateTimeZoneFactory->get($row->statusTimeTimeZone)),
				);
			}
			$this->statusHistory[$applicationId] = $items;
		}
		return $this->statusHistory[$applicationId];
	}


	/**
	 * @param list<TrainingApplicationStatus> $statuses
	 */
	public function historyContainsStatuses(array $statuses, int $applicationId): bool
	{
		$result = false;
		foreach ($this->getStatusHistory($applicationId) as $history) {
			if (in_array($history->getStatus(), $statuses, true)) {
				$result = true;
				break;
			}
		}
		return $result;
	}


	public function deleteHistoryRecord(int $applicationId, int $recordId): void
	{
		$result = $this->database->fetch(
			'SELECT
				key_status AS statusId,
				status_time AS statusTime
			FROM training_application_status_history
			WHERE key_application = ? AND id_status_log = ?',
			$applicationId,
			$recordId,
		);
		if ($result === null) {
			return;
		}
		assert(is_int($result->statusId));
		assert($result->statusTime instanceof DateTime);

		Debugger::log(sprintf(
			'Deleting status history record for application id: %d, history record id: %d, status: %d, status time: %s',
			$applicationId,
			$recordId,
			$result->statusId,
			$result->statusTime->format(DateTimeInterface::ATOM),
		));
		$this->database->query(
			'DELETE FROM training_application_status_history WHERE key_application = ? AND id_status_log = ?',
			$applicationId,
			$recordId,
		);
	}

}
