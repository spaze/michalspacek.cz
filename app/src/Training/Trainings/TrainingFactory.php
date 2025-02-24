<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Trainings;

use MichalSpacekCz\Formatter\TexyFormatter;
use Nette\Database\Row;

final readonly class TrainingFactory
{

	public function __construct(
		private TexyFormatter $texyFormatter,
	) {
	}


	public function createFromDatabaseRow(Row $row): Training
	{
		assert(is_int($row->id));
		assert(is_string($row->action));
		assert(is_string($row->name));
		assert(is_string($row->description));
		assert(is_string($row->content));
		assert($row->upsell === null || is_string($row->upsell));
		assert($row->prerequisites === null || is_string($row->prerequisites));
		assert($row->audience === null || is_string($row->audience));
		assert($row->capacity === null || is_int($row->capacity));
		assert($row->price === null || is_int($row->price));
		assert($row->studentDiscount === null || is_int($row->studentDiscount));
		assert($row->materials === null || is_string($row->materials));
		assert(is_int($row->custom));
		assert($row->successorId === null || is_int($row->successorId));
		assert($row->discontinuedId === null || is_int($row->discontinuedId));

		return new Training(
			$row->id,
			$row->action,
			$this->texyFormatter->translate($row->name),
			$this->texyFormatter->translate($row->description),
			$this->texyFormatter->translate($row->content),
			$row->upsell !== null ? $this->texyFormatter->translate($row->upsell) : null,
			$row->prerequisites !== null ? $this->texyFormatter->translate($row->prerequisites) : null,
			$row->audience !== null ? $this->texyFormatter->translate($row->audience) : null,
			$row->capacity,
			$row->price,
			$row->studentDiscount,
			$row->materials !== null ? $this->texyFormatter->translate($row->materials) : null,
			(bool)$row->custom,
			$row->successorId,
			$row->discontinuedId,
		);
	}

}
