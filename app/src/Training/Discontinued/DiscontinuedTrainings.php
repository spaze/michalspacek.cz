<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Discontinued;

use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Templating\DefaultTemplate;
use Nette\Http\IResponse;

final readonly class DiscontinuedTrainings
{

	public function __construct(
		private TypedDatabase $typedDatabase,
		private IResponse $httpResponse,
	) {
	}


	/**
	 * Get all discontinued trainings with description.
	 *
	 * @return list<DiscontinuedTraining>
	 */
	public function getAllDiscontinued(): array
	{
		$query = $this->typedDatabase->fetchAll(
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
			assert(is_int($row->id));
			assert(is_string($row->description));
			assert(is_string($row->href));
			assert(is_string($row->training));
			$trainings[$row->id]['description'] = $row->description;
			$trainings[$row->id]['href'] = $row->href;
			if (!isset($trainings[$row->id]['trainings'])) {
				$trainings[$row->id]['trainings'] = [];
			}
			$trainings[$row->id]['trainings'][] = $row->training;
		}
		$result = [];
		foreach ($trainings as $training) {
			$result[] = new DiscontinuedTraining($training['description'], $training['trainings'], $training['href']);
		}
		return $result;
	}


	public function maybeMarkAsDiscontinued(DefaultTemplate $template, ?int $discontinuedId): void
	{
		$template->discontinued = [];
		if ($discontinuedId === null) {
			return;
		}

		$query = $this->typedDatabase->fetchAll(
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
			$discontinuedId,
		);
		$trainings = [];
		foreach ($query as $row) {
			assert(is_string($row->training));
			$trainings[] = $row->training;
		}
		if (isset($row)) {
			assert(is_string($row->description));
			assert(is_string($row->href));
			$template->discontinued = [new DiscontinuedTraining($row->description, $trainings, $row->href)];
			$this->httpResponse->setCode(IResponse::S410_Gone);
			return;
		}
	}

}
