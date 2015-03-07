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

	/** @var \Nette\Database\Context */
	protected $database;

	private $statusIds = array();

	private $childrenStatuses = array();

	private $parentStatuses = array();

	private $descendantStatuses = array();

	private $statusCallbacks = array();


	/**
	 * @param \Nette\Database\Context $context
	 */
	public function __construct(\Nette\Database\Context $context)
	{
		$this->database = $context;
		$this->statusCallbacks[Statuses::STATUS_NOTIFIED] = array($this, 'notifyCallback');
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
		return array($this->getStatusId(self::STATUS_CANCELED) => self::STATUS_CANCELED) + $this->getDescendantStatuses(self::STATUS_CANCELED);
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

		if (isset($this->statusCallbacks[$status]) && is_callable($this->statusCallbacks[$status])) {
			call_user_func($this->statusCallbacks[$status], $applicationId);
		}

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

}
