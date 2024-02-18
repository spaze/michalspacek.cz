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

	public const string STATUS_CREATED = 'CREATED'; // 1
	public const string STATUS_TENTATIVE = 'TENTATIVE'; // 2
	public const string STATUS_INVITED = 'INVITED'; // 3
	public const string STATUS_SIGNED_UP = 'SIGNED_UP'; // 4
	public const string STATUS_INVOICE_SENT = 'INVOICE_SENT'; // 5
	public const string STATUS_NOTIFIED = 'NOTIFIED'; // 6
	public const string STATUS_ATTENDED = 'ATTENDED'; // 7
	public const string STATUS_MATERIALS_SENT = 'MATERIALS_SENT'; // 8
	public const string STATUS_ACCESS_TOKEN_USED = 'ACCESS_TOKEN_USED'; // 9
	public const string STATUS_CANCELED = 'CANCELED'; // 10
	public const string STATUS_IMPORTED = 'IMPORTED'; // 13
	public const string STATUS_NON_PUBLIC_TRAINING = 'NON_PUBLIC_TRAINING'; // 14
	public const string STATUS_REMINDED = 'REMINDED'; // 15
	public const string STATUS_PAID_AFTER = 'PAID_AFTER'; // 16
	public const string STATUS_INVOICE_SENT_AFTER = 'INVOICE_SENT_AFTER'; // 17
	public const string STATUS_PRO_FORMA_INVOICE_SENT = 'PRO_FORMA_INVOICE_SENT'; // 18
	public const string STATUS_SPAM = 'SPAM'; // 19

	/** @var array<string, int> */
	private array $statusIds = [];

	/** @var array<string, array<int, string>> */
	private array $childrenStatuses = [];

	/** @var array<string, array<int, string>> */
	private array $parentStatuses = [];

	/** @var array<string, array<int, string>> */
	private array $descendantStatuses = [];


	public function __construct(
		private readonly Explorer $database,
		private readonly TrainingApplicationStatusHistory $statusHistory,
	) {
	}


	/**
	 * @throws TrainingStatusIdNotIntException
	 */
	public function getStatusId(string $status): int
	{
		if (!isset($this->statusIds[$status])) {
			$statusId = $this->database->fetchField(
				'SELECT id_status FROM training_application_status WHERE status = ?',
				$status,
			);
			if (!is_int($statusId)) {
				throw new TrainingStatusIdNotIntException($status, $statusId);
			}
			$this->statusIds[$status] = $statusId;
		}
		return $this->statusIds[$status];
	}


	/**
	 * @return array<int, string>
	 */
	public function getAttendedStatuses(): array
	{
		return [$this->getStatusId(self::STATUS_ATTENDED) => self::STATUS_ATTENDED] + $this->getDescendantStatuses(self::STATUS_ATTENDED);
	}


	/**
	 * @return array<int, string>
	 */
	public function getAllowFilesStatuses(): array
	{
		return [
			$this->getStatusId(self::STATUS_INVOICE_SENT) => self::STATUS_INVOICE_SENT,
			$this->getStatusId(self::STATUS_PRO_FORMA_INVOICE_SENT) => self::STATUS_PRO_FORMA_INVOICE_SENT,
			$this->getStatusId(self::STATUS_REMINDED) => self::STATUS_REMINDED,
		] + $this->getAttendedStatuses();
	}


	/**
	 * @return array<int, string>
	 */
	public function getDiscardedStatuses(): array
	{
		return $this->getCanceledStatus() + $this->getDescendantStatuses(self::STATUS_CANCELED);
	}


	/**
	 * @return array<int, string>
	 */
	public function getCanceledStatus(): array
	{
		return [$this->getStatusId(self::STATUS_CANCELED) => self::STATUS_CANCELED];
	}


	/**
	 * @return array<int, string>
	 */
	public function getInitialStatuses(): array
	{
		return $this->getChildrenStatuses(self::STATUS_CREATED);
	}


	/**
	 * @param string $parent
	 * @return array<int, string>
	 */
	public function getChildrenStatuses(string $parent): array
	{
		if (!isset($this->childrenStatuses[$parent])) {
			$this->childrenStatuses[$parent] = $this->database->fetchPairs(
				'SELECT
					st.id_status,
					st.status
				FROM training_application_status_flow f
					JOIN training_application_status sf ON sf.id_status = f.key_status_from
					JOIN training_application_status st ON st.id_status = f.key_status_to
				WHERE sf.status = ?',
				$parent,
			);
		}
		return $this->childrenStatuses[$parent];
	}


	/**
	 * @param string $child
	 * @return array<int, string>
	 */
	public function getParentStatuses(string $child): array
	{
		if (!isset($this->parentStatuses[$child])) {
			$this->parentStatuses[$child] = $this->database->fetchPairs(
				'SELECT
					sf.id_status,
					sf.status
				FROM training_application_status_flow f
					JOIN training_application_status sf ON sf.id_status = f.key_status_from
					JOIN training_application_status st ON st.id_status = f.key_status_to
				WHERE st.status = ?',
				$child,
			);
		}
		return $this->parentStatuses[$child];
	}


	/**
	 * @param string $parent
	 * @param int $applicationId
	 * @return array<int, string>
	 */
	public function getChildrenStatusesForApplicationId(string $parent, int $applicationId): array
	{
		$children = $this->getChildrenStatuses($parent);
		if ($parent === self::STATUS_ATTENDED) {
			$removeStatus = ($this->sendInvoiceAfter($applicationId) ? self::STATUS_MATERIALS_SENT : self::STATUS_INVOICE_SENT_AFTER);
			unset($children[$this->getStatusId($removeStatus)]);
		}
		return $children;
	}


	/**
	 * @param string $parent
	 * @return array<int, string>
	 */
	private function getDescendantStatuses(string $parent): array
	{
		if (!isset($this->descendantStatuses[$parent])) {
			$statuses = $this->getChildrenStatuses($parent);
			foreach ($statuses as $status) {
				$statuses += $this->getDescendantStatuses($status);
			}
			$this->descendantStatuses[$parent] = $statuses;
		}
		return $this->descendantStatuses[$parent];
	}


	/**
	 * Needs to be wrapped in transaction, not for public consumption,
	 * use updateStatus(), updateStatusCallback() or updateStatusReturnCallback() instead.
	 *
	 * @param int $applicationId
	 * @param string $status
	 * @param string|null $date
	 * @throws TrainingApplicationDoesNotExistException
	 */
	private function setStatus(int $applicationId, string $status, ?string $date): void
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


	public function updateStatus(int $applicationId, string $status, ?string $date = null): void
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
	public function updateStatusCallbackReturnId(callable $callback, string $status, ?string $date): int
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


	public function updateStatusCallback(int $applicationId, string $status, ?string $date, callable $callback): void
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
			$this->statusHistory->historyContainsStatuses([self::STATUS_PAID_AFTER, self::STATUS_PRO_FORMA_INVOICE_SENT], $applicationId)
			&& !$this->statusHistory->historyContainsStatuses([self::STATUS_INVOICE_SENT], $applicationId)
		);
	}

}
