<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Discontinued;

use MichalSpacekCz\ShouldNotHappenException;
use Nette\Database\Explorer;

class DiscontinuedTrainings
{

	public function __construct(
		private readonly Explorer $database,
	) {
	}


	/**
	 * Get all discontinued trainings with description.
	 *
	 * @return list<DiscontinuedTraining>
	 */
	public function getAllDiscontinued(): array
	{
		$query = $this->database->fetchAll(
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
		foreach ($query as $row) {
			$id = $row->id;
			if (!is_int($id)) {
				throw new ShouldNotHappenException(sprintf("Discontinued training id is a %s not an integer", get_debug_type($id)));
			}
			$trainings[$id]['description'] = (string)$row->description;
			$trainings[$id]['href'] = (string)$row->href;
			if (!isset($trainings[$id]['trainings'])) {
				$trainings[$id]['trainings'] = [];
			}
			$trainings[$id]['trainings'][] = (string)$row->training;
		}
		$result = [];
		foreach ($trainings as $training) {
			$result[] = new DiscontinuedTraining($training['description'], $training['trainings'], $training['href']);
		}
		return $result;
	}


	/**
	 * Get discontinued trainings with description.
	 *
	 * @param int $id
	 * @return DiscontinuedTraining|null
	 */
	public function getDiscontinued(int $id): ?DiscontinuedTraining
	{
		$query = $this->database->fetchAll(
			'SELECT
				td.description,
				t.name AS training,
				td.href
			FROM trainings_discontinued td
				JOIN trainings t ON t.key_discontinued = td.id_trainings_discontinued
			WHERE
				td.id_trainings_discontinued = ?
			ORDER BY
				t.id_training',
			$id,
		);
		$trainings = [];
		foreach ($query as $row) {
			$trainings[] = $row->training;
		}
		return empty($row) ? null : new DiscontinuedTraining($row->description, $trainings, $row->href);
	}

}
