<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use MichalSpacekCz\Form\TrainingDateFormFactory;

final readonly class TrainingDateInputsFactory
{

	public function __construct(
		private TrainingDateFormFactory $trainingDateFormFactory,
	) {
	}


	public function createFor(TrainingDate $trainingDate): TrainingDateInputs
	{
		return new TrainingDateInputs($this->trainingDateFormFactory, $trainingDate);
	}


	public function create(): TrainingDateInputs
	{
		return new TrainingDateInputs($this->trainingDateFormFactory, null);
	}

}
