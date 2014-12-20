<?php
namespace MichalSpacekCz\Training;

/**
 * Trainings model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Trainings
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \Bare\Formatter\Texy */
	protected $texyFormatter;

	/** @var \MichalSpacekCz\Training\Dates */
	protected $trainingDates;


	/**
	 * @param \Nette\Database\Context $context
	 * @param \Bare\Next\Formatter\Texy $texyFormatter
	 * @param \MichalSpacekCz\Training\Dates $trainingDates
	 */
	public function __construct(
		\Nette\Database\Context $context,
		\Bare\Next\Formatter\Texy $texyFormatter,
		Dates $trainingDates
	)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
		$this->trainingDates = $trainingDates;
	}


	public function get($name)
	{
		$result = $this->database->fetch(
			'SELECT
				action,
				name,
				description,
				description_company AS descriptionCompany,
				content,
				upsell,
				prerequisites,
				audience,
				original_href AS originalHref,
				capacity,
				price,
				price_company AS priceCompany,
				student_discount AS studentDiscount,
				materials
			FROM trainings
			WHERE action = ?',
			$name
		);

		if ($result) {
			$result->description   = $this->texyFormatter->format($result->description);
			$result->descriptionCompany = $this->texyFormatter->format($result->descriptionCompany);
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
				v.action AS venueAction,
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
			Dates::STATUS_TENTATIVE,
			Dates::STATUS_CONFIRMED
		);
		$dates = array();
		foreach ($result as $row) {
			$row->tentative        = ($row->status == Dates::STATUS_TENTATIVE);
			$row->lastFreeSeats    = $this->trainingDates->lastFreeSeats($row->start);
			$row->venueDescription = $this->texyFormatter->format($row->venueDescription);
			$row->cooperationDescription = $this->texyFormatter->format($row->cooperationDescription);
			$dates[$row->dateId]   = $row;
		}
		return $dates;
	}


	public function lastFreeSeatsAnyTraining(array $trainings)
	{
		$lastFreeSeats = false;
		foreach ($trainings as $training) {
			if ($this->trainingDates->lastFreeSeatsAnyDate((array)$training->dates)) {
				$lastFreeSeats = true;
				break;
			}
		}
		return $lastFreeSeats;
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


	public function getNames()
	{
		$result = $this->database->fetchAll(
			'SELECT
				t.id_training AS id,
				t.action,
				t.name
			FROM trainings t
			ORDER BY
				t.id_training'
		);
		return $result;
	}


	public function getCooperations()
	{
		$result = $this->database->fetchAll(
			'SELECT
				c.id_cooperation AS id,
				c.name
			FROM training_cooperations c
			ORDER BY
				c.id_cooperation'
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
			$this->database->getConnection()->getSupplementalDriver()->applyLimit($query, $limit, null);
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
