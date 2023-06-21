<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use Contributte\Translation\Translator;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Training\Dates\UpcomingTraining;
use MichalSpacekCz\Training\Exceptions\TrainingDoesNotExistException;
use Nette\Database\Explorer;
use Nette\Database\Row;

class Trainings
{

	/** @var array<int, Row> */
	private array $trainingsById = [];


	public function __construct(
		private readonly Explorer $database,
		private readonly TexyFormatter $texyFormatter,
		private readonly FreeSeats $freeSeats,
		private readonly Translator $translator,
	) {
	}


	/**
	 * @param string $name
	 * @return Row<mixed>
	 * @throws TrainingDoesNotExistException
	 */
	public function get(string $name): Row
	{
		return $this->getTraining($name, false);
	}


	/**
	 * @param string $name
	 * @return Row<mixed>
	 * @throws TrainingDoesNotExistException
	 */
	public function getIncludingCustom(string $name): Row
	{
		return $this->getTraining($name, true);
	}


	/**
	 * @param string $name
	 * @param bool $includeCustom
	 * @return Row<mixed>
	 * @throws TrainingDoesNotExistException
	 */
	private function getTraining(string $name, bool $includeCustom): Row
	{
		/** @var Row<mixed>|null $result */
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
			$includeCustom,
		);

		if (!$result) {
			throw new TrainingDoesNotExistException(name: $name);
		}
		return $this->texyFormatter->formatTraining($result);
	}


	/**
	 * @param int $id
	 * @return Row<mixed>
	 * @throws TrainingDoesNotExistException
	 */
	public function getById(int $id): Row
	{
		if (!isset($this->trainingsById[$id])) {
			/** @var Row<mixed>|null $result */
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
				$this->translator->getDefaultLocale(),
			);

			if (!$result) {
				throw new TrainingDoesNotExistException(id: $id);
			} else {
				$this->trainingsById[$id] = $this->texyFormatter->formatTraining($result);
			}
		}
		return $this->trainingsById[$id];
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
			$this->translator->getDefaultLocale(),
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
			$this->translator->getDefaultLocale(),
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
		return $this->database->fetchAll(
			'SELECT
				c.id_cooperation AS id,
				c.name
			FROM training_cooperations c
			ORDER BY
				c.id_cooperation',
		);
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
			$this->translator->getDefaultLocale(),
		);
	}


	/**
	 * Get all discontinued trainings with description.
	 *
	 * @return array<int, array<string, string|string[]>>
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
				t.id_training',
		);
		$trainings = [];
		foreach ($result as $row) {
			/** @var int $id */
			$id = $row->id;
			$trainings[$id]['description'] = (string)$row->description;
			$trainings[$id]['href'] = (string)$row->href;
			if (!isset($trainings[$id]['trainings'])) {
				$trainings[$id]['trainings'] = [];
			}
			$trainings[$id]['trainings'][] = (string)$row->training;
		}
		return $trainings;
	}


	/**
	 * Get discontinued trainings with description.
	 *
	 * @param int $id
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


	/**
	 * @param array<int, int> $dateIds
	 */
	public function deletePersonalData(array $dateIds): void
	{
		$this->database->query(
			'UPDATE training_applications SET ? WHERE key_date IN (?)',
			[
				'name' => null,
				'email' => null,
				'company' => null,
				'street' => null,
				'city' => null,
				'zip' => null,
				'country' => null,
				'company_id' => null,
				'company_tax_id' => null,
				'note' => null,
			],
			$dateIds,
		);
	}

}
