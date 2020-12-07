<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use DateTime;
use DateTimeZone;
use Exception;
use Nette\Database\Context;
use Nette\Database\Row;
use Tracy\Debugger;

class Statuses
{

	public const STATUS_CREATED             = 'CREATED';              // 1
	public const STATUS_TENTATIVE           = 'TENTATIVE';            // 2
	public const STATUS_INVITED             = 'INVITED';              // 3
	public const STATUS_SIGNED_UP           = 'SIGNED_UP';            // 4
	public const STATUS_INVOICE_SENT        = 'INVOICE_SENT';         // 5
	public const STATUS_NOTIFIED            = 'NOTIFIED';             // 6
	public const STATUS_ATTENDED            = 'ATTENDED';             // 7
	public const STATUS_MATERIALS_SENT      = 'MATERIALS_SENT';       // 8
	public const STATUS_ACCESS_TOKEN_USED   = 'ACCESS_TOKEN_USED';    // 9
	public const STATUS_CANCELED            = 'CANCELED';             // 10
	public const STATUS_IMPORTED            = 'IMPORTED';             // 13
	public const STATUS_NON_PUBLIC_TRAINING = 'NON_PUBLIC_TRAINING';  // 14
	public const STATUS_REMINDED            = 'REMINDED';             // 15
	public const STATUS_PAID_AFTER          = 'PAID_AFTER';           // 16
	public const STATUS_INVOICE_SENT_AFTER  = 'INVOICE_SENT_AFTER';   // 17
	public const STATUS_PRO_FORMA_INVOICE_SENT = 'PRO_FORMA_INVOICE_SENT'; // 18

	/** @var Context */
	protected $database;

	/** @var array<string, integer> */
	private $statusIds = [];

	/** @var array<string, array<integer, string>> */
	private $childrenStatuses = [];

	/** @var array<string, array<integer, string>> */
	private $parentStatuses = [];

	/** @var array<string, array<integer, string>> */
	private $descendantStatuses = [];

	/** @var array<integer, array<integer, Row>> */
	private $statusHistory = [];


	public function __construct(Context $context)
	{
		$this->database = $context;
	}


	public function getStatusId(string $status): int
	{
		if (!isset($this->statusIds[$status])) {
			$this->statusIds[$status] = $this->database->fetchField(
				'SELECT id_status FROM training_application_status WHERE status = ?',
				$status
			);
		}
		return $this->statusIds[$status];
	}


	/**
	 * @return array<integer, string>
	 */
	public function getAttendedStatuses(): array
	{
		return array($this->getStatusId(self::STATUS_ATTENDED) => self::STATUS_ATTENDED) + $this->getDescendantStatuses(self::STATUS_ATTENDED);
	}


	/**
	 * @return array<integer, string>
	 */
	public function getAllowFilesStatuses(): array
	{
		return [
			$this->getStatusId(self::STATUS_INVOICE_SENT) => self::STATUS_INVOICE_SENT,
			$this->getStatusId(self::STATUS_REMINDED) => self::STATUS_REMINDED,
		] + $this->getAttendedStatuses();
	}


	/**
	 * @return array<integer, string>
	 */
	public function getDiscardedStatuses(): array
	{
		return $this->getCanceledStatus() + $this->getDescendantStatuses(self::STATUS_CANCELED);
	}


	/**
	 * @return array<integer, string>
	 */
	public function getCanceledStatus(): array
	{
		return array($this->getStatusId(self::STATUS_CANCELED) => self::STATUS_CANCELED);
	}


	/**
	 * @return array<integer, string>
	 */
	public function getInitialStatuses(): array
	{
		return $this->getChildrenStatuses(self::STATUS_CREATED);
	}


	/**
	 * @param string $parent
	 * @return array<integer, string>
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
				$parent
			);
		}
		return $this->childrenStatuses[$parent];
	}


	/**
	 * @param string $child
	 * @return array<integer, string>
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
				$child
			);
		}
		return $this->parentStatuses[$child];
	}


	/**
	 * @param string $parent
	 * @param integer $applicationId
	 * @return array<integer, string>
	 */
	public function getChildrenStatusesForApplicationId(string $parent, int $applicationId): array
	{
		$children = $this->getChildrenStatuses($parent);
		if ($parent === self::STATUS_ATTENDED) {
			$status = ($this->historyContainsStatuses([self::STATUS_PAID_AFTER, self::STATUS_PRO_FORMA_INVOICE_SENT], $applicationId) ? self::STATUS_MATERIALS_SENT : self::STATUS_INVOICE_SENT_AFTER);
			unset($children[$this->getStatusId($status)]);
		}
		return $children;
	}


	/**
	 * @param string $parent
	 * @return array<integer, string>
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
	 * @param integer $applicationId
	 * @param string $status
	 * @param string|null $date
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
			$applicationId
		);

		$datetime = new DateTime($date ?? '');

		Debugger::log(sprintf(
			'Changing status for application id: %d; old status: %s, old status time: %s; new status: %s, new status time: %s',
			$applicationId,
			$prevStatus->statusId,
			$prevStatus->statusTime->format(DateTime::ATOM),
			$statusId,
			$datetime->format(DateTime::ATOM),
		));

		/** @var DateTimeZone|false $timeZone */
		$timeZone = $datetime->getTimezone();
		$this->database->query(
			'UPDATE training_applications SET ? WHERE id_application = ?',
			array(
				'key_status'           => $statusId,
				'status_time'          => $datetime,
				'status_time_timezone' => ($timeZone ? $timeZone->getName() : date_default_timezone_get()),
			),
			$applicationId
		);

		$this->database->query(
			'INSERT INTO training_application_status_history',
			array(
				'key_application'      => $applicationId,
				'key_status'           => $prevStatus->statusId,
				'status_time'          => $prevStatus->statusTime,
				'status_time_timezone' => $prevStatus->statusTimeTimeZone,
			)
		);
	}


	public function updateStatus(int $applicationId, string $status, ?string $date = null): void
	{
		$this->database->beginTransaction();
		try {
			$this->setStatus($applicationId, $status, $date);
			$this->database->commit();
		} catch (Exception $e) {
			$this->database->rollBack();
		}
	}


	public function updateStatusCallbackReturnId(callable $callback, string $status, ?string $date): int
	{
		$applicationId = null;
		$this->database->beginTransaction();
		try {
			/** @var integer $applicationId */
			$applicationId = $callback();
			$this->setStatus($applicationId, $status, $date);
			$this->database->commit();
		} catch (Exception $e) {
			$this->database->rollBack();
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
		} catch (Exception $e) {
			$this->database->rollBack();
		}
	}


	/**
	 * @param integer $applicationId
	 * @return Row[]
	 */
	public function getStatusHistory(int $applicationId): array
	{
		if (!isset($this->statusHistory[$applicationId])) {
			$this->statusHistory[$applicationId] = $this->database->fetchAll(
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
				$applicationId
			);
			foreach ($this->statusHistory[$applicationId] as &$row) {
				$row->statusTime->setTimezone(new DateTimeZone($row->statusTimeTimeZone));
				unset($row->statusTimeTimeZone);
			}
		}
		return $this->statusHistory[$applicationId];
	}


	/**
	 * @param string[] $statuses
	 * @param integer $applicationId
	 * @return boolean
	 */
	public function historyContainsStatuses(array $statuses, int $applicationId): bool
	{
		$result = false;
		foreach ($this->getStatusHistory($applicationId) as $history) {
			if (in_array($history->status, $statuses)) {
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
			$recordId
		);
		if (!$result) {
			return;
		}

		Debugger::log(sprintf(
			'Deleting status history record for application id: %d, history record id: %d, status: %d, status time: %s',
			$applicationId,
			$recordId,
			$result->statusId,
			$result->statusTime->format(DateTime::ATOM)
		));
		$this->database->query(
			'DELETE FROM training_application_status_history WHERE key_application = ? AND id_status_log = ?',
			$applicationId,
			$recordId
		);
	}

}
