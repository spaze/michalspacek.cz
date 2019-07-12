<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Formatter\Texy;
use Nette\Database\Context;
use Nette\Database\Row;
use Nette\Localization\ITranslator;
use Nette\Utils\ArrayHash;

class Trainings
{

	/** @var Context */
	protected $database;

	/** @var Texy */
	protected $texyFormatter;

	/** @var Dates */
	protected $trainingDates;

	/** @var ITranslator */
	protected $translator;

	/** @var Row[] */
	protected $trainingsById = [];


	public function __construct(Context $context, Texy $texyFormatter, Dates $trainingDates, ITranslator $translator)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
		$this->trainingDates = $trainingDates;
		$this->translator = $translator;
	}


	public function get(string $name): ?Row
	{
		return $this->getTraining($name, false);
	}


	public function getIncludingCustom(string $name): ?Row
	{
		return $this->getTraining($name, true);
	}


	private function getTraining(string $name, bool $includeCustom): ?Row
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


	public function getById(int $id): ?Row
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
	 * @param integer $trainingId
	 * @return Row[]
	 */
	public function getDates(int $trainingId): array
	{
		$dates = $this->trainingDates->getDates($trainingId);
		foreach ($dates as $date) {
			$date->venueDescription = $this->texyFormatter->format($date->venueDescription);
			$date->cooperationDescription = $this->texyFormatter->format($date->cooperationDescription);
		}
		return $dates;
	}


	/**
	 * @param ArrayHash[] $trainings
	 * @return boolean
	 */
	public function lastFreeSeatsAnyTraining(array $trainings): bool
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


	/**
	 * @return Row[]
	 */
	public function getAllTrainings(): array
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
	 *
	 * @return Row[]
	 */
	public function getNames(): array
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
	 *
	 * @return Row[]
	 */
	public function getNamesIncludingCustomDiscontinued(): array
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


	/**
	 * @return Row[]
	 */
	public function getCooperations(): array
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


	public function getActionById(int $id): string
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
	 * @return array<integer, array<string, string|string[]>>
	 */
	public function getAllDiscontinued(): array
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
			/** @var integer $id */
			$id = $row->id;
			$trainings[$id]['description'] = $row->description;
			$trainings[$id]['href'] = $row->href;
			if (!isset($trainings[$id]['trainings'])) {
				$trainings[$id]['trainings'] = [];
			}
			$trainings[$id]['trainings'][] = $row->training;
		}
		return $trainings;
	}


	/**
	 * Get discontinued trainings with description.
	 *
	 * @param integer $id
	 * @return array<string, string|string[]>|null
	 */
	public function getDiscontinued(int $id): ?array
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
