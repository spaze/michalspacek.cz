<?php
namespace MichalSpacekCz\Training;

/**
 * Training application statuses model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Statuses
{

	const STATUS_CREATED             = 'CREATED';              // 1
	const STATUS_TENTATIVE           = 'TENTATIVE';            // 2
	const STATUS_INVITED             = 'INVITED';              // 3
	const STATUS_SIGNED_UP           = 'SIGNED_UP';            // 4
	const STATUS_INVOICE_SENT        = 'INVOICE_SENT';         // 5
	const STATUS_NOTIFIED            = 'NOTIFIED';             // 6
	const STATUS_ATTENDED            = 'ATTENDED';             // 7
	const STATUS_MATERIALS_SENT      = 'MATERIALS_SENT';       // 8
	const STATUS_ACCESS_TOKEN_USED   = 'ACCESS_TOKEN_USED';    // 9
	const STATUS_CANCELED            = 'CANCELED';             // 10
	const STATUS_IMPORTED            = 'IMPORTED';             // 13
	const STATUS_NON_PUBLIC_TRAINING = 'NON_PUBLIC_TRAINING';  // 14
	const STATUS_REMINDED            = 'REMINDED';             // 15
	const STATUS_PAID_AFTER          = 'PAID_AFTER';           // 16
	const STATUS_INVOICE_SENT_AFTER  = 'INVOICE_SENT_AFTER';   // 17
	const STATUS_PRO_FORMA_INVOICE_SENT = 'PRO_FORMA_INVOICE_SENT'; // 18

	/** @var \Nette\Database\Context */
	protected $database;

	private $statusIds = array();

	private $childrenStatuses = array();

	private $parentStatuses = array();

	private $descendantStatuses = array();

	private $statusHistory = array();


	/**
	 * @param \Nette\Database\Context $context
	 */
	public function __construct(\Nette\Database\Context $context)
	{
		$this->database = $context;
	}


	public function getStatusId($status)
	{
		if (!isset($this->statusIds[$status])) {
			$this->statusIds[$status] = $this->database->fetchField(
				'SELECT id_status FROM training_application_status WHERE status = ?',
				$status
			);
		}
		return $this->statusIds[$status];
	}


	public function getAttendedStatuses()
	{
		return array($this->getStatusId(self::STATUS_ATTENDED) => self::STATUS_ATTENDED) + $this->getDescendantStatuses(self::STATUS_ATTENDED);
	}


	public function getDiscardedStatuses()
	{
		return $this->getCanceledStatus() + $this->getDescendantStatuses(self::STATUS_CANCELED);
	}


	/**
	 * Get canceled status.
	 *
	 * @return array(id => status)
	 */
	public function getCanceledStatus()
	{
		return array($this->getStatusId(self::STATUS_CANCELED) => self::STATUS_CANCELED);
	}


	public function getInitialStatuses()
	{
		return $this->getChildrenStatuses(self::STATUS_CREATED);
	}


	public function getChildrenStatuses($parent)
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


	public function getParentStatuses($child)
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


	public function getChildrenStatusesForApplicationId($parent, $applicationId)
	{
		$children = $this->getChildrenStatuses($parent);
		if ($parent === self::STATUS_ATTENDED) {
			$status = ($this->historyContainsStatuses([self::STATUS_PAID_AFTER, self::STATUS_PRO_FORMA_INVOICE_SENT], $applicationId) ? self::STATUS_MATERIALS_SENT : self::STATUS_INVOICE_SENT_AFTER);
			unset($children[$this->getStatusId($status)]);
		}
		return $children;
	}


	private function getDescendantStatuses($parent)
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
	 */
	private function setStatus($applicationId, $status, $date)
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

		$datetime = new \DateTime($date);
		$this->database->query(
			'UPDATE training_applications SET ? WHERE id_application = ?',
			array(
				'key_status'           => $statusId,
				'status_time'          => $datetime,
				'status_time_timezone' => $datetime->getTimezone()->getName(),
			),
			$applicationId
		);

		$result = $this->database->query(
			'INSERT INTO training_application_status_history',
			array(
				'key_application'      => $applicationId,
				'key_status'           => $prevStatus->statusId,
				'status_time'          => $prevStatus->statusTime,
				'status_time_timezone' => $prevStatus->statusTimeTimeZone,
			)
		);

		return $result;
	}


	public function updateStatus($applicationId, $status, $date = null)
	{
		$this->database->beginTransaction();
		try {
			$this->setStatus($applicationId, $status, $date);
			$this->database->commit();
		} catch (\Exception $e) {
			$this->database->rollBack();
		}
	}


	public function updateStatusCallback(callable $callback, $status, $date)
	{
		$applicationId = null;
		$this->database->beginTransaction();
		try {
			$applicationId = $callback();
			$this->setStatus($applicationId, $status, $date);
			$this->database->commit();
		} catch (\Exception $e) {
			$this->database->rollBack();
		}
		return $applicationId;
	}


	public function updateStatusReturnCallback($applicationId, $status, $date, callable $callback)
	{
		$result = null;
		$this->database->beginTransaction();
		try {
			$result = $callback();
			$this->setStatus($applicationId, $status, $date);
			$this->database->commit();
		} catch (\Exception $e) {
			$this->database->rollBack();
		}
		return $result;
	}


	public function getStatusHistory($applicationId)
	{
		if (!isset($this->statusHistory[$applicationId])) {
			$this->statusHistory[$applicationId] = $this->database->fetchAll(
				'SELECT
					h.key_status AS statusId,
					s.status,
					h.status_time AS statusTime,
					h.status_time_timezone AS statusTimeTimeZone
				FROM training_application_status s
					JOIN training_application_status_history h ON h.key_status = s.id_status
				WHERE h.key_application = ?',
				$applicationId
			);
			foreach ($this->statusHistory[$applicationId] as &$row) {
				$row->statusTime->setTimezone(new \DateTimeZone($row->statusTimeTimeZone));
				unset($row->statusTimeTimeZone);
			}
		}
		return $this->statusHistory[$applicationId];
	}


	public function historyContainsStatuses(array $statuses, $applicationId)
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


}
