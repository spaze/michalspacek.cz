<?php
namespace MichalSpacekCz;

/**
 * Training application statuses model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingStatuses
{

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
		return array($this->getStatusId(TrainingApplications::STATUS_ATTENDED) => TrainingApplications::STATUS_ATTENDED) + $this->getDescendantStatuses(TrainingApplications::STATUS_ATTENDED);
	}


	public function getDiscardedStatuses()
	{
		return array($this->getStatusId(TrainingApplications::STATUS_CANCELED) => TrainingApplications::STATUS_CANCELED) + $this->getDescendantStatuses(TrainingApplications::STATUS_CANCELED);
	}


	public function getInitialStatuses()
	{
		return $this->getChildrenStatuses(TrainingApplications::STATUS_CREATED);
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
