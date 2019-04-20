<?php
namespace MichalSpacekCz\Training;

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
	 * @return \Nette\Database\Row|null
	 */
	public function get($name): ?\Nette\Database\Row
	{
		return $this->getTraining($name, false);
	}


	/**
	 * Get training info including custom trainings.
	 *
	 * @param string $name
	 * @return \Nette\Database\Row|null
	 */
	public function getIncludingCustom($name): ?\Nette\Database\Row
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
				t.capacity,
				t.price,
				t.student_discount AS studentDiscount,
				t.materials,
				t.custom,
				t.key_successor AS successorId,
				t.key_discontinued AS discontinuedId
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
					t.capacity,
					t.price,
					t.student_discount AS studentDiscount,
					t.materials,
					t.custom,
					t.key_successor AS successorId,
					t.key_discontinued AS discontinuedId
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
		$dates = $this->trainingDates->getDates($id);
		foreach ($dates as $date) {
			$date->venueDescription = $this->texyFormatter->format($date->venueDescription);
			$date->cooperationDescription = $this->texyFormatter->format($date->cooperationDescription);
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
				v.city AS venueCity,
				d.note
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
			$this->texyFormatter->formatTraining($training);
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
				AND t.key_discontinued IS NULL
				AND l.language = ?
			ORDER BY
				t.order IS NULL, t.order',
			$this->translator->getDefaultLocale()
		);

		foreach ($result as $training) {
			$this->texyFormatter->formatTraining($training);
		}
		return $result;
	}


	/**
	 * Get all training names including custom and discontinued training names.
	 */
	public function getNamesIncludingCustomDiscontinued()
	{
		$result = $this->database->fetchAll(
			'SELECT
				t.id_training AS id,
				a.action,
				t.name,
				t.custom,
				t.key_successor AS successorId,
				t.key_discontinued AS discontinuedId
			FROM trainings t
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions a ON ta.key_url_action = a.id_url_action
				JOIN languages l ON a.key_language = l.id_language
			WHERE
				l.language = ?
			ORDER BY
				t.order IS NULL, t.order',
			$this->translator->getDefaultLocale()
		);

		foreach ($result as $training) {
			$this->texyFormatter->formatTraining($training);
		}
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


	/**
	 * Get all discontinued trainings with description.
	 *
	 * @return array
	 */
	public function getAllDiscontinued()
	{
		$result = $this->database->fetchAll(
			'SELECT
				td.id_trainings_discontinued AS id,
				td.description,
				t.name AS training,
				td.href
			FROM trainings_discontinued td
				JOIN trainings t ON t.key_discontinued = td.id_trainings_discontinued
			ORDER BY
				td.id_trainings_discontinued,
				t.id_training'
		);
		$trainings = [];
		foreach ($result as $row) {
			$trainings[$row->id]['description'] = $row->description;
			$trainings[$row->id]['href'] = $row->href;
			if (!isset($trainings[$row->id]['trainings'])) {
				$trainings[$row->id]['trainings'] = [];
			}
			$trainings[$row->id]['trainings'][] = $row->training;
		}
		return $trainings;
	}


	/**
	 * Get discontinued trainings with description.
	 *
	 * @param integer $id
	 * @return array
	 */
	public function getDiscontinued($id)
	{
		$sql = 'SELECT
				td.description,
				t.name AS training,
				td.href
			FROM trainings_discontinued td
				JOIN trainings t ON t.key_discontinued = td.id_trainings_discontinued
			WHERE
				td.id_trainings_discontinued = ?
			ORDER BY
				t.id_training';
		$trainings = [];
		foreach ($this->database->fetchAll($sql, $id) as $row) {
			$trainings[] = $row->training;
		}
		return (empty($row) ? null : [
			'description' => $row->description,
			'href' => $row->href,
			'trainings' => $trainings,
		]);
	}

}
