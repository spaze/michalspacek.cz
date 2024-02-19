<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationStatuses;

use DateTime;
use Exception;
use MichalSpacekCz\Training\Exceptions\CannotUpdateTrainingApplicationStatusException;
use MichalSpacekCz\Training\Exceptions\TrainingApplicationDoesNotExistException;
use MichalSpacekCz\Training\Exceptions\TrainingStatusIdNotIntException;
use Nette\Database\Explorer;
use Tracy\Debugger;

class TrainingApplicationStatuses
{

	/** @var array<string, int> */
	private array $statusIds = [];

	/** @var array<string, array<int, TrainingApplicationStatus>> */
	private array $childrenStatuses = [];

	/** @var array<string, array<int, TrainingApplicationStatus>> */
	private array $parentStatuses = [];

	/** @var array<string, array<int, TrainingApplicationStatus>> */
	private array $descendantStatuses = [];


	public function __construct(
		private readonly Explorer $database,
		private readonly TrainingApplicationStatusHistory $statusHistory,
	) {
	}


	/**
	 * @throws TrainingStatusIdNotIntException
	 */
	public function getStatusId(TrainingApplicationStatus $status): int
	{
		if (!isset($this->statusIds[$status->value])) {
			$statusId = $this->database->fetchField(
				'SELECT id_status FROM training_application_status WHERE status = ?',
				$status->value,
			);
			if (!is_int($statusId)) {
				throw new TrainingStatusIdNotIntException($status, $statusId);
			}
			$this->statusIds[$status->value] = $statusId;
		}
		return $this->statusIds[$status->value];
	}


	/**
	 * @return array<int, TrainingApplicationStatus>
	 */
	public function getAttendedStatuses(): array
	{
		return [$this->getStatusId(TrainingApplicationStatus::Attended) => TrainingApplicationStatus::Attended] + $this->getDescendantStatuses(TrainingApplicationStatus::Attended);
	}


	/**
	 * @return array<int, TrainingApplicationStatus>
	 */
	public function getAllowFilesStatuses(): array
	{
		return [
			$this->getStatusId(TrainingApplicationStatus::InvoiceSent) => TrainingApplicationStatus::InvoiceSent,
			$this->getStatusId(TrainingApplicationStatus::ProFormaInvoiceSent) => TrainingApplicationStatus::ProFormaInvoiceSent,
			$this->getStatusId(TrainingApplicationStatus::Reminded) => TrainingApplicationStatus::Reminded,
		] + $this->getAttendedStatuses();
	}


	/**
	 * @return array<int, TrainingApplicationStatus>
	 */
	public function getDiscardedStatuses(): array
	{
		return $this->getCanceledStatus() + $this->getDescendantStatuses(TrainingApplicationStatus::Canceled);
	}


	/**
	 * @return array<int, TrainingApplicationStatus>
	 */
	public function getCanceledStatus(): array
	{
		return [$this->getStatusId(TrainingApplicationStatus::Canceled) => TrainingApplicationStatus::Canceled];
	}


	/**
	 * @return array<int, TrainingApplicationStatus>
	 */
	public function getInitialStatuses(): array
	{
		return $this->getChildrenStatuses(TrainingApplicationStatus::Created);
	}


	/**
	 * @return array<int, TrainingApplicationStatus>
	 */
	public function getChildrenStatuses(TrainingApplicationStatus $parent): array
	{
		if (!isset($this->childrenStatuses[$parent->value])) {
			$this->childrenStatuses[$parent->value] = [];
			$statuses = $this->database->fetchPairs(
				'SELECT
					st.id_status,
					st.status
				FROM training_application_status_flow f
					JOIN training_application_status sf ON sf.id_status = f.key_status_from
					JOIN training_application_status st ON st.id_status = f.key_status_to
				WHERE sf.status = ?',
				$parent->value,
			);
			foreach ($statuses as $status) {
				$this->childrenStatuses[$parent->value][] = TrainingApplicationStatus::from($status);
			}
		}
		return $this->childrenStatuses[$parent->value];
	}


	/**
	 * @return array<int, TrainingApplicationStatus>
	 */
	public function getParentStatuses(TrainingApplicationStatus $child): array
	{
		if (!isset($this->parentStatuses[$child->value])) {
			$statuses = $this->database->fetchPairs(
				'SELECT
					sf.id_status,
					sf.status
				FROM training_application_status_flow f
					JOIN training_application_status sf ON sf.id_status = f.key_status_from
					JOIN training_application_status st ON st.id_status = f.key_status_to
				WHERE st.status = ?',
				$child->value,
			);
			foreach ($statuses as $status) {
				$this->parentStatuses[$child->value][] = TrainingApplicationStatus::from($status);
			}
		}
		return $this->parentStatuses[$child->value];
	}


	/**
	 * @return array<int, TrainingApplicationStatus>
	 */
	public function getChildrenStatusesForApplicationId(TrainingApplicationStatus $parent, int $applicationId): array
	{
		$children = $this->getChildrenStatuses($parent);
		if ($parent === TrainingApplicationStatus::Attended) {
			$removeStatus = $this->sendInvoiceAfter($applicationId) ? TrainingApplicationStatus::MaterialsSent : TrainingApplicationStatus::InvoiceSentAfter;
			unset($children[$this->getStatusId($removeStatus)]);
		}
		return $children;
	}


	/**
	 * @return array<int, TrainingApplicationStatus>
	 */
	private function getDescendantStatuses(TrainingApplicationStatus $parent): array
	{
		if (!isset($this->descendantStatuses[$parent->value])) {
			$statuses = $this->getChildrenStatuses($parent);
			foreach ($statuses as $status) {
				$statuses += $this->getDescendantStatuses($status);
			}
			$this->descendantStatuses[$parent->value] = $statuses;
		}
		return $this->descendantStatuses[$parent->value];
	}


	/**
	 * Needs to be wrapped in transaction, not for public consumption,
	 * use updateStatus(), updateStatusCallback() or updateStatusReturnCallback() instead.
	 *
	 * @throws TrainingApplicationDoesNotExistException
	 */
	private function setStatus(int $applicationId, TrainingApplicationStatus $status, ?string $date): void
	{
		$statusId = $this->getStatusId($status);

		$prevStatus = $this->database->fetch(
			'SELECT
				key_status AS statusId,
				status_time AS statusTime,
				status_time_timezone AS statusTimeTimeZone
			FROM
				training_applications
			WHERE
				id_application = ?',
			$applicationId,
		);
		if (!$prevStatus) {
			throw new TrainingApplicationDoesNotExistException($applicationId);
		}

		$datetime = new DateTime($date ?? '');

		Debugger::log(sprintf(
			'Changing status for application id: %d; old status: %s, old status time: %s; new status: %s, new status time: %s',
			$applicationId,
			$prevStatus->statusId,
			$prevStatus->statusTime->format(DateTime::ATOM),
			$statusId,
			$datetime->format(DateTime::ATOM),
		));

		$timeZone = $datetime->getTimezone()->getName();
		$this->database->query(
			'UPDATE training_applications SET ? WHERE id_application = ?',
			[
				'key_status' => $statusId,
				'status_time' => $datetime,
				'status_time_timezone' => $timeZone,
			],
			$applicationId,
		);

		$this->database->query(
			'INSERT INTO training_application_status_history',
			[
				'key_application' => $applicationId,
				'key_status' => $prevStatus->statusId,
				'status_time' => $prevStatus->statusTime,
				'status_time_timezone' => $prevStatus->statusTimeTimeZone,
			],
		);
	}


	public function updateStatus(int $applicationId, TrainingApplicationStatus $status, ?string $date = null): void
	{
		$this->database->beginTransaction();
		try {
			$this->setStatus($applicationId, $status, $date);
			$this->database->commit();
		} catch (Exception) {
			$this->database->rollBack();
		}
	}


	/**
	 * @param callable(): int $callback
	 * @throws CannotUpdateTrainingApplicationStatusException
	 */
	public function updateStatusCallbackReturnId(callable $callback, TrainingApplicationStatus $status, ?string $date): int
	{
		$this->database->beginTransaction();
		try {
			$applicationId = $callback();
			$this->setStatus($applicationId, $status, $date);
			$this->database->commit();
		} catch (Exception $e) {
			$this->database->rollBack();
			throw new CannotUpdateTrainingApplicationStatusException(previous: $e);
		}
		return $applicationId;
	}


	public function updateStatusCallback(int $applicationId, TrainingApplicationStatus $status, ?string $date, callable $callback): void
	{
		$this->database->beginTransaction();
		try {
			$callback();
			$this->setStatus($applicationId, $status, $date);
			$this->database->commit();
		} catch (Exception) {
			$this->database->rollBack();
		}
	}


	public function sendInvoiceAfter(int $applicationId): bool
	{
		return (
			$this->statusHistory->historyContainsStatuses([TrainingApplicationStatus::PaidAfter, TrainingApplicationStatus::ProFormaInvoiceSent], $applicationId)
			&& !$this->statusHistory->historyContainsStatuses([TrainingApplicationStatus::InvoiceSent], $applicationId)
		);
	}

}
