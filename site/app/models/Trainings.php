<?php
namespace MichalSpacekCz;

/**
 * Trainings model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Trainings extends BaseModel
{

	const LAST_FREE_SEATS_THRESHOLD_DAYS = 7;


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
		$query = "SELECT
				d.id_date AS dateId,
				t.action,
				t.name,
				s.status,
				d.start,
				d.public,
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
		foreach ($this->database->fetchAll($query, $all, $all, TrainingDates::STATUS_TENTATIVE, TrainingDates::STATUS_CONFIRMED) as $row) {
			$date = array(
				'dateId'        => $row->dateId,
				'tentative'     => ($row->status == TrainingDates::STATUS_TENTATIVE),
				'lastFreeSeats' => $this->lastFreeSeats($row->start),
				'start'         => $row->start,
				'public'        => $row->public,
				'status'        => $row->status,
				'name'          => $row->name,
				'venueName'     => $row->venueName,
				'venueCity'     => $row->venueCity,
			);
			$upcoming[$row->action] = \Nette\ArrayHash::from(array(
				'action' => $row->action,
				'name'   => $row->name,
				'dates'  => (isset($upcoming[$row->action]->dates)
					? $upcoming[$row->action]->dates = (array)$upcoming[$row->action]->dates + array($row->dateId => $date)
					: array($row->dateId => $date)
				),
			));
		}

		return $upcoming;
	}


	public function get($name)
	{
		$result = $this->database->fetch(
			'SELECT
				action,
				name,
				description,
				content,
				upsell,
				prerequisites,
				audience,
				original_href AS originalHref,
				capacity,
				price,
				student_discount AS studentDiscount,
				materials
			FROM trainings
			WHERE action = ?',
			$name
		);

		if ($result) {
			$result->description   = $this->texyFormatter->format($result->description);
			$result->content       = $this->texyFormatter->format($result->content);
			$result->upsell        = $this->texyFormatter->format($result->upsell);
			$result->prerequisites = $this->texyFormatter->format($result->prerequisites);
			$result->audience      = $this->texyFormatter->format($result->audience);
			$result->materials     = $this->texyFormatter->format($result->materials);
		}

		return $result;
	}


	public function getDates($name)
	{
		$result = $this->database->fetchAll(
			"SELECT
				d.id_date AS dateId,
				d.start,
				d.end,
				s.status,
				v.href AS venueHref,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.address AS venueAddress,
				v.city AS venueCity,
				v.description AS venueDescription,
				c.description AS cooperationDescription
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_date_status s ON d.key_status = s.id_status
				LEFT JOIN training_cooperations c ON d.key_cooperation = c.id_cooperation
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
						d2.public
						AND t2.action = ?
						AND d2.end > NOW()
						AND s2.status IN (?, ?)
					GROUP BY
						t2.action, d2.key_venue
				) u ON t.action = u.action AND v.id_venue = u.key_venue AND d.start = u.start
			ORDER BY
				d.start",
			$name,
			TrainingDates::STATUS_TENTATIVE,
			TrainingDates::STATUS_CONFIRMED
		);
		$dates = array();
		foreach ($result as $row) {
			$row->tentative        = ($row->status == TrainingDates::STATUS_TENTATIVE);
			$row->lastFreeSeats    = $this->lastFreeSeats($row->start);
			$row->venueDescription = $this->texyFormatter->format($row->venueDescription);
			$row->cooperationDescription = $this->texyFormatter->format($row->cooperationDescription);
			$dates[$row->dateId]   = $row;
		}
		return $dates;
	}


	protected function lastFreeSeats(\DateTime $start)
	{
		$now = new \DateTime();
		return ($start->diff($now)->days <= self::LAST_FREE_SEATS_THRESHOLD_DAYS && $start > $now);
	}


	public function lastFreeSeatsAnyTraining(array $trainings)
	{
		$lastFreeSeats = false;
		foreach ($trainings as $training) {
			if ($this->lastFreeSeatsAnyDate((array)$training->dates)) {
				$lastFreeSeats = true;
				break;
			}
		}
		return $lastFreeSeats;
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


	public function getPastDates($name)
	{
		return $this->database->fetchPairs(
			"SELECT
				d.id_date AS dateId,
				d.start
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_date_status s ON d.key_status = s.id_status
			WHERE t.action = ?
				AND d.end < NOW()
				AND s.status = 'CONFIRMED'
				AND d.public
			ORDER BY
				start DESC",
			$name
		);
	}


	public function getAllTrainings()
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
			ORDER BY
				d.start'
		);
		return $result;
	}


	public function getByDate($dateId)
	{
		$result = $this->database->fetch(
			'SELECT
				d.id_date AS dateId,
				t.action,
				t.name,
				d.start,
				d.end,
				s.status,
				v.href AS venueHref,
				v.name AS venueName,
				v.name_extended AS venueNameExtended,
				v.city AS venueCity
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_venues v ON d.key_venue = v.id_venue
				JOIN training_date_status s ON d.key_status = s.id_status
			WHERE
				d.id_date = ?',
			$dateId
		);
		return $result;
	}


	public function getReviews($name, $limit = null)
	{
		$query = 'SELECT
				COALESCE(r.name, a.name) AS name,
				COALESCE(r.company, a.company) AS company,
				r.review,
				r.href
			FROM
				training_reviews r
				LEFT JOIN training_applications a ON r.key_application = a.id_application
				JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON t.id_training = d.key_training
			WHERE
				t.action = ?
				AND NOT r.hidden
			ORDER BY r.ranking IS NULL, r.ranking, r.added DESC';

		if ($limit !== null) {
			$this->database->getSupplementalDriver()->applyLimit($query, $limit, null);
		}

		$reviews = $this->database->fetchAll($query, $name);
		foreach ($reviews as &$review) {
			$review['review'] = $this->texyFormatter->format($review['review']);
		}
		return $reviews;
	}


	public function getReviewByApplicationId($applicationId)
	{
		return $this->database->fetch('SELECT
				a.name AS applicationName,
				r.name,
				a.company AS applicationCompany,
				r.company,
				r.review,
				r.href,
				r.hidden,
				d.id_date AS dateId
			FROM
				training_applications a
				JOIN training_dates d ON a.key_date = d.id_date
				LEFT JOIN training_reviews r ON a.id_application = r.key_application
			WHERE
				a.id_application = ?',
			$applicationId
		);
	}


	public function addUpdateReview($applicationId, $name, $company, $review, $href, $hidden)
	{
		$datetime = new \DateTime();
		return $this->database->query(
				'INSERT INTO
					training_reviews ?
				ON DUPLICATE KEY UPDATE ?',
				array(
					'key_application' => $applicationId,
					'name' => $name,
					'company' => $company,
					'review' => $review,
					'href' => $href,
					'hidden' => $hidden,
					'added' => $datetime,
					'added_timezone' => $datetime->getTimezone()->getName(),
				),
				array(
					'name' => $name,
					'company' => $company,
					'review' => $review,
					'href' => $href,
					'hidden' => $hidden,
				)
		);
	}


}