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

	/** @var \Nette\Database\Connection */
	protected $database;

	private $statusIds = array();

	private $childrenStatuses = array();

	private $parentStatuses = array();

	private $descendantStatuses = array();

	/**
	 * @param \Nette\Database\Connection $connection
	 */
	public function __construct(\Nette\Database\Connection $connection)
	{
		$this->database = $connection;
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

}
