<?php
namespace MichalSpacekCz\Training;

/**
 * Training dates model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Dates
{

	const STATUS_CREATED   = 'CREATED';    // 1
	const STATUS_TENTATIVE = 'TENTATIVE';  // 2
	const STATUS_CONFIRMED = 'CONFIRMED';  // 3
	const STATUS_CANCELED  = 'CANCELED';   // 4

	const LAST_FREE_SEATS_THRESHOLD_DAYS = 7;

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var Statuses */
	protected $trainingStatuses;

	private $statusIds = array();

	private $upcomingDates = array();


	/**
	 * @param \Nette\Database\Context $context
	 * @param Statuses $trainingStatuses
	 */
	public function __construct(
		\Nette\Database\Context $context,
		Statuses $trainingStatuses
	)
	{
		$this->database = $context;
		$this->trainingStatuses = $trainingStatuses;
	}


	public function get($dateId)
	{
		$result = $this->database->fetch(
			'SELECT
				d.id_date AS dateId,
				t.id_training AS trainingId,
				t.action,
				t.name,
				t.price,
				t.student_discount AS studentDiscount,
				d.start,
				d.end,
				d.public,
				s.status,
				v.id_venue AS venueId,
				v.href AS venueHref,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.city AS venueCity,
				c.id_cooperation AS cooperationId
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_date_status s ON d.key_status = s.id_status
				LEFT JOIN training_cooperations c ON d.key_cooperation = c.id_cooperation
			WHERE
				d.id_date = ?',
			$dateId
		);
		return $result;
	}


	public function getWithUnpaid()
	{
		$result = $this->database->fetchAll(
			'SELECT
				d.id_date AS dateId,
				t.action,
				t.name,
				d.start,
				d.end,
				d.public,
				s.status,
				v.href AS venueHref,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.city AS venueCity
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_date_status s ON d.key_status = s.id_status
			WHERE EXISTS (
				SELECT
					1
				FROM
					training_applications a
				WHERE
					a.key_date = d.id_date
					AND a.paid IS NULL
					AND a.invoice_id IS NOT NULL
					AND a.key_status NOT IN (?)
			)
			ORDER BY
				d.start',
			array_keys($this->trainingStatuses->getDiscardedStatuses())
		);
		return $result;
	}


	public function update($dateId, $training, $venue, $start, $end, $status, $public, $cooperation)
	{
		$this->database->query(
			'UPDATE training_dates SET ? WHERE id_date = ?',
			array(
				'key_training'    => $training,
				'key_venue'       => $venue,
				'start'           => new \DateTime($start),
				'end'             => new \DateTime($end),
				'key_status'      => $status,
				'public'          => $public,
				'key_cooperation' => (empty($cooperation) ? null : $cooperation),
			),
			$dateId
		);
	}


	public function add($training, $venue, $start, $end, $status, $public, $cooperation)
	{
		$this->database->query(
			'INSERT INTO training_dates',
			array(
				'key_training'    => $training,
				'key_venue'       => $venue,
				'start'           => new \DateTime($start),
				'end'             => new \DateTime($end),
				'key_status'      => $status,
				'public'          => $public,
				'key_cooperation' => (empty($cooperation) ? null : $cooperation),
			)
		);
		return $this->database->getInsertId();
	}


	public function getStatuses()
	{
		$result = $this->database->fetchAll(
			'SELECT
				s.id_status AS id,
				s.status
			FROM training_date_status s
			ORDER BY
				s.id_status'
		);
		return $result;
	}


	public function getStatusId($status)
	{
		if (!isset($this->statusIds[$status])) {
			$this->statusIds[$status] = $this->database->fetchField(
				'SELECT id_status FROM training_date_status WHERE status = ?',
				$status
			);
		}
		return $this->statusIds[$status];
	}


	public function getPublicUpcoming()
	{
		return $this->getUpcoming(false);
	}


	public function getPublicUpcomingIds()
	{
		$upcomingIds = array();
		foreach ($this->getPublicUpcoming() as $training) {
			foreach ($training->dates as $date) {
				$upcomingIds[] = $date->dateId;
			}
		}
		return $upcomingIds;
	}


	public function getAllUpcoming()
	{
		return $this->getUpcoming(true);
	}


	/**
	 * Get upcoming trainings.
	 *
	 * @param boolean $all Whether to include non-public trainings
	 *
	 * @return array
	 */
	private function getUpcoming($all)
	{
		if (!isset($this->upcomingDates[$all])) {
			$query = "SELECT
					d.id_date AS dateId,
					t.action,
					t.name,
					s.status,
					d.start,
					d.public,
					v.id_venue AS venueId,
					v.name AS venueName,
					v.city as venueCity
				FROM training_dates d
					JOIN trainings t ON d.key_training = t.id_training
					JOIN training_date_status s ON d.key_status = s.id_status
					JOIN training_venues v ON d.key_venue = v.id_venue
					JOIN (
						SELECT
							t2.action,
							d2.key_venue,
							MIN(d2.start) AS start
						FROM
							trainings t2
							JOIN training_dates d2 ON t2.id_training = d2.key_training
							JOIN training_date_status s2 ON d2.key_status = s2.id_status
						WHERE
							(d2.public != ? OR TRUE = ?)
							AND d2.end > NOW()
							AND s2.status IN (?, ?)
						GROUP BY
							t2.action, d2.key_venue
					) u ON t.action = u.action AND v.id_venue = u.key_venue AND d.start = u.start
				ORDER BY
					t.id_training, d.start";

			$upcoming = array();
			foreach ($this->database->fetchAll($query, $all, $all, Dates::STATUS_TENTATIVE, Dates::STATUS_CONFIRMED) as $row) {
				$date = array(
					'dateId'        => $row->dateId,
					'tentative'     => ($row->status == Dates::STATUS_TENTATIVE),
					'lastFreeSeats' => $this->lastFreeSeats($row->start),
					'start'         => $row->start,
					'public'        => $row->public,
					'status'        => $row->status,
					'name'          => $row->name,
					'venueId'       => $row->venueId,
					'venueName'     => $row->venueName,
					'venueCity'     => $row->venueCity,
				);
				$upcoming[$row->action] = \Nette\Utils\ArrayHash::from(array(
					'action' => $row->action,
					'name'   => $row->name,
					'dates'  => (isset($upcoming[$row->action]->dates)
						? $upcoming[$row->action]->dates = (array)$upcoming[$row->action]->dates + array($row->dateId => $date)
						: array($row->dateId => $date)
					),
				));
			}
			$this->upcomingDates[$all] = $upcoming;
		}

		return $this->upcomingDates[$all];
	}


	public function getAllTrainingsInterval($from, $to = null)
	{
		$result = $this->database->fetchAll(
			'SELECT
				d.id_date AS dateId,
				t.action,
				t.name,
				d.start,
				d.end,
				d.public,
				s.status,
				v.href AS venueHref,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.city AS venueCity
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_date_status s ON d.key_status = s.id_status
			WHERE d.end BETWEEN ? AND ?
			ORDER BY
				d.start',
			new \DateTime($from),
			new \DateTime($to)
		);
		return $result;
	}


	public function lastFreeSeats(\DateTime $start)
	{
		$now = new \DateTime();
		return ($start->diff($now)->days <= self::LAST_FREE_SEATS_THRESHOLD_DAYS && $start > $now);
	}


	public function lastFreeSeatsAnyDate(array $dates)
	{
		$lastFreeSeats = false;
		foreach ($dates as $date) {
			if ($date->lastFreeSeats) {
				$lastFreeSeats = true;
				break;
			}
		}
		return $lastFreeSeats;
	}

}
