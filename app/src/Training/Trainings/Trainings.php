<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Trainings;

use Contributte\Translation\Translator;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Training\Exceptions\TrainingDoesNotExistException;
use Nette\Database\Explorer;

final class Trainings
{

	/** @var array<int, Training> */
	private array $trainingsById = [];


	public function __construct(
		private readonly Explorer $database,
		private readonly TypedDatabase $typedDatabase,
		private readonly Translator $translator,
		private readonly TrainingFactory $trainingFactory,
	) {
	}


	/**
	 * @throws TrainingDoesNotExistException
	 */
	public function get(string $name): Training
	{
		return $this->getTraining($name, false);
	}


	/**
	 * @throws TrainingDoesNotExistException
	 */
	public function getIncludingCustom(string $name): Training
	{
		return $this->getTraining($name, true);
	}


	/**
	 * @throws TrainingDoesNotExistException
	 */
	private function getTraining(string $name, bool $includeCustom): Training
	{
		$result = $this->database->fetch(
			'SELECT
				t.id_training AS id,
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
		return $this->trainingFactory->createFromDatabaseRow($result);
	}


	/**
	 * @throws TrainingDoesNotExistException
	 */
	public function getById(int $id): Training
	{
		if (!isset($this->trainingsById[$id])) {
			$result = $this->database->fetch(
				'SELECT
					t.id_training AS id,
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
			}
			$this->trainingsById[$id] = $this->trainingFactory->createFromDatabaseRow($result);
		}
		return $this->trainingsById[$id];
	}


	/**
	 * Get all training names without custom training names.
	 *
	 * @return list<Training>
	 */
	public function getNames(): array
	{
		$result = $this->typedDatabase->fetchAll(
			'SELECT
				t.id_training AS id,
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
				NOT t.custom
				AND t.key_successor IS NULL
				AND t.key_discontinued IS NULL
				AND l.language = ?
			ORDER BY
				t.order IS NULL, t.order',
			$this->translator->getDefaultLocale(),
		);

		$trainings = [];
		foreach ($result as $row) {
			$trainings[] = $this->trainingFactory->createFromDatabaseRow($row);
		}
		return $trainings;
	}


	/**
	 * Get all training names including custom and discontinued training names.
	 *
	 * @return list<Training>
	 */
	public function getNamesIncludingCustomDiscontinued(): array
	{
		$result = $this->typedDatabase->fetchAll(
			'SELECT
				t.id_training AS id,
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
				l.language = ?
			ORDER BY
				t.order IS NULL, t.order',
			$this->translator->getDefaultLocale(),
		);
		$trainings = [];
		foreach ($result as $row) {
			$trainings[] = $this->trainingFactory->createFromDatabaseRow($row);
		}
		return $trainings;
	}


	/**
	 * @return array<int, string>
	 */
	public function getCooperations(): array
	{
		return $this->typedDatabase->fetchPairsIntString(
			'SELECT
				c.id_cooperation,
				c.name
			FROM training_cooperations c
			ORDER BY
				c.id_cooperation',
		);
	}


	public function getActionById(int $id): string
	{
		return $this->typedDatabase->fetchFieldString(
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
