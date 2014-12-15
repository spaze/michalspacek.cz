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

	/** @var \Nette\Database\Connection */
	protected $database;

	/** @var \MichalSpacekCz\Training\Statuses */
	protected $trainingStatuses;

	private $statusIds = array();

	/**
	 * @param \Nette\Database\Connection $connection
	 * @param \MichalSpacekCz\Training\Statuses $trainingStatuses
	 */
	public function __construct(
		\Nette\Database\Connection $connection,
		Statuses $trainingStatuses
	)
	{
		$this->database = $connection;
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
				'key_status'          => $status,
				'public'          => $public,
				'key_cooperation' => (empty($cooperation) ? null : $cooperation),
			),
			$dateId
		);
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


}
