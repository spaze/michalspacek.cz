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

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/** @var Dates */
	protected $trainingDates;

	/** @var \Nette\Localization\ITranslator */
	protected $translator;

	/** @var \Nette\Database\Row[] */
	protected $trainingsById = [];


	/**
	 * @param \Nette\Database\Context $context
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param Dates $trainingDates
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(
		\Nette\Database\Context $context,
		\MichalSpacekCz\Formatter\Texy $texyFormatter,
		Dates $trainingDates,
		\Nette\Localization\ITranslator $translator
	)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
		$this->trainingDates = $trainingDates;
		$this->translator = $translator;
	}


	/**
	 * Get predefined training info.
	 *
	 * @param string $name
	 * @return \Nette\Database\Row
	 */
	public function get($name)
	{
		return $this->getTraining($name, false);
	}


	/**
	 * Get training info including custom trainings.
	 *
	 * @param string $name
	 * @return \Nette\Database\Row
	 */
	public function getIncludingCustom($name)
	{
		return $this->getTraining($name, true);
	}


	/**
	 * Get training info.
	 *
	 * @param string $name
	 * @param boolean $includeCustom
	 * @return \Nette\Database\Row|null
	 */
	private function getTraining($name, $includeCustom): ?\Nette\Database\Row
	{
		$result = $this->database->fetch(
			'SELECT
				t.id_training AS trainingId,
				a.action,
				t.name,
				t.description,
				t.content,
				t.upsell,
				t.prerequisites,
				t.audience,
				t.original_href AS originalHref,
				t.capacity,
				t.price,
				t.student_discount AS studentDiscount,
				t.materials,
				t.custom,
				t.key_successor AS successorId
			FROM trainings t
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions a ON ta.key_url_action = a.id_url_action
				JOIN languages l ON a.key_language = l.id_language
			WHERE
				a.action = ?
				AND l.language = ?
				AND (t.custom = ? OR TRUE = ?)',
			$name,
			$this->translator->getDefaultLocale(),
			$includeCustom,
			$includeCustom
		);

		return ($result ? $this->texyFormatter->formatTraining($result) : null);
	}


	/**
	 * Get training info by id.
	 *
	 * @param integer $id
	 * @return \Nette\Database\Row|null
	 */
	public function getById($id): ?\Nette\Database\Row
	{
		if (!array_key_exists($id, $this->trainingsById)) {
			$result = $this->database->fetch(
				'SELECT
					t.id_training AS trainingId,
					a.action,
					t.name,
					t.description,
					t.content,
					t.upsell,
					t.prerequisites,
					t.audience,
					t.original_href AS originalHref,
					t.capacity,
					t.price,
					t.student_discount AS studentDiscount,
					t.materials,
					t.custom,
					t.key_successor AS successorId
				FROM trainings t
					JOIN training_url_actions ta ON t.id_training = ta.key_training
					JOIN url_actions a ON ta.key_url_action = a.id_url_action
					JOIN languages l ON a.key_language = l.id_language
				WHERE
					t.id_training = ?
					AND l.language = ?',
				$id,
				$this->translator->getDefaultLocale()
			);
			$this->trainingsById[$id] = ($result ? $this->texyFormatter->formatTraining($result) : null);
		}
		return $this->trainingsById[$id];
	}


	/**
	 * Get training dates by training id.
	 *
	 * @param integer $id
	 * @return \Nette\Database\Row[]
	 */
	public function getDates($id)
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
						t2.id_training,
						d2.key_venue,
						MIN(d2.start) AS start
					FROM
						trainings t2
						JOIN training_dates d2 ON t2.id_training = d2.key_training
						JOIN training_date_status s2 ON d2.key_status = s2.id_status
					WHERE
						d2.public
						AND t2.id_training = ?
						AND d2.end > NOW()
						AND s2.status IN (?, ?)
					GROUP BY
						t2.id_training, d2.key_venue
				) u ON t.id_training = u.id_training AND v.id_venue = u.key_venue AND d.start = u.start
			ORDER BY
				d.start",
			$id,
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
				a.action,
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
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions a ON ta.key_url_action = a.id_url_action
				JOIN languages l ON a.key_language = l.id_language
			WHERE
				l.language = ?
			ORDER BY
				d.start DESC',
			$this->translator->getDefaultLocale()
		);

		foreach ($result as $training) {
			$training = $this->texyFormatter->formatTraining($training);
		}
		return $result;
	}


	/**
	 * Get all training names without custom training names.
	 */
	public function getNames()
	{
		$result = $this->database->fetchAll(
			'SELECT
				t.id_training AS id,
				a.action,
				t.name
			FROM trainings t
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions a ON ta.key_url_action = a.id_url_action
				JOIN languages l ON a.key_language = l.id_language
			WHERE
				NOT t.custom
				AND t.key_successor IS NULL
				AND l.language = ?
			ORDER BY
				t.order IS NULL, t.order',
			$this->translator->getDefaultLocale()
		);

		foreach ($result as $training) {
			$training = $this->texyFormatter->formatTraining($training);
		}
		return $result;
	}


	/**
	 * Get all training names including custom training names.
	 */
	public function getNamesIncludingCustom()
	{
		$result = $this->database->fetchAll(
			'SELECT
				t.id_training AS id,
				a.action,
				t.name,
				t.custom,
				t.key_successor AS successorId
			FROM trainings t
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions a ON ta.key_url_action = a.id_url_action
				JOIN languages l ON a.key_language = l.id_language
			WHERE
				l.language = ?
			ORDER BY
				t.id_training',
			$this->translator->getDefaultLocale()
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


	/**
	 * Get reviews by training id.
	 *
	 * @param integer $id
	 * @param integer|null $limit
	 * @return \Nette\Database\Row[]
	 */
	public function getReviews($id, $limit = null)
	{
		$query = 'SELECT
				COALESCE(r.name, a.name) AS name,
				COALESCE(r.company, a.company) AS company,
				r.job_title AS jobTitle,
				r.review,
				r.href
			FROM
				training_reviews r
				LEFT JOIN training_applications a ON r.key_application = a.id_application
				JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON t.id_training = d.key_training
				LEFT JOIN trainings t2 ON t2.id_training = t.key_successor
			WHERE
				(t.id_training = ? OR t2.id_training = ?)
				AND NOT r.hidden
			ORDER BY r.ranking IS NULL, r.ranking, r.added DESC';

		if ($limit !== null) {
			$this->database->getConnection()->getSupplementalDriver()->applyLimit($query, $limit, null);
		}

		$reviews = $this->database->fetchAll($query, $id, $id);
		foreach ($reviews as &$review) {
			$review['review'] = $this->texyFormatter->format($review['review']);
		}
		return $reviews;
	}


	/**
	 * Get review by application id.
	 *
	 * @param integer $applicationId
	 * @return \Nette\Database\Row
	 * @throws \RuntimeException
	 */
	public function getReviewByApplicationId($applicationId)
	{
		$result = $this->database->fetch('SELECT
				a.name AS applicationName,
				r.name,
				a.company AS applicationCompany,
				r.company,
				r.job_title AS jobTitle,
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

		if (!$result) {
			throw new \RuntimeException("No application id {$applicationId}, yet");
		}

		return $result;
	}


	public function addUpdateReview($applicationId, $name, $company, $jobTitle, $review, $href, $hidden)
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
					'job_title' => $jobTitle,
					'review' => $review,
					'href' => $href,
					'hidden' => $hidden,
					'added' => $datetime,
					'added_timezone' => $datetime->getTimezone()->getName(),
				),
				array(
					'name' => $name,
					'company' => $company,
					'job_title' => $jobTitle,
					'review' => $review,
					'href' => $href,
					'hidden' => $hidden,
				)
		);
	}


	/**
	 * Return training action name by id.
	 * @param integer $id
	 * @return string
	 */
	public function getActionById($id)
	{
		return $this->database->fetchField(
			'SELECT
				a.action
			FROM trainings t
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions a ON ta.key_url_action = a.id_url_action
				JOIN languages l ON a.key_language = l.id_language
			WHERE
				t.id_training = ?
				AND l.language = ?',
			$id,
			$this->translator->getDefaultLocale()
		);
	}

}
