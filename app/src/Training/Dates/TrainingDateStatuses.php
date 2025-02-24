<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\ShouldNotHappenException;

final readonly class TrainingDateStatuses
{

	public function __construct(
		private TypedDatabase $typedDatabase,
	) {
	}


	/**
	 * @return list<TrainingDateStatus>
	 */
	public function getStatuses(): array
	{
		$result = $this->typedDatabase->fetchAll(
			'SELECT
				s.id_status AS id,
				s.status,
				description
			FROM training_date_status s
			ORDER BY
				s.id_status',
		);
		$statuses = [];
		foreach ($result as $row) {
			assert(is_int($row->id));
			assert(is_string($row->status));
			assert(is_string($row->description));
			$status = TrainingDateStatus::from($row->status);
			if ($status->id() !== $row->id || $status->description() !== $row->description) {
				throw new ShouldNotHappenException("Training data status enum doesn't match database values for status '{$status->value}'");
			}
			$statuses[] = $status;
		}
		return $statuses;
	}

}
