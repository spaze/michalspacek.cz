<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Trainings;

use MichalSpacekCz\Formatter\TexyFormatter;
use Nette\Database\Row;

readonly class TrainingFactory
{

	public function __construct(
		private TexyFormatter $texyFormatter,
	) {
	}


	public function createFromDatabaseRow(Row $row): Training
	{
		return new Training(
			$row->id,
			$row->action,
			$this->texyFormatter->translate($row->name),
			$this->texyFormatter->translate($row->description),
			$this->texyFormatter->translate($row->content),
			$row->upsell ? $this->texyFormatter->translate($row->upsell) : null,
			$row->prerequisites ? $this->texyFormatter->translate($row->prerequisites) : null,
			$row->audience ? $this->texyFormatter->translate($row->audience) : null,
			$row->capacity,
			$row->price,
			$row->studentDiscount,
			$row->materials ? $this->texyFormatter->translate($row->materials) : null,
			(bool)$row->custom,
			$row->successorId,
			$row->discontinuedId,
		);
	}

}
