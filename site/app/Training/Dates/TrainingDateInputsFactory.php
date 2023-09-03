<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use MichalSpacekCz\Form\TrainingDateFormFactory;

class TrainingDateInputsFactory
{

	public function __construct(
		private readonly TrainingDateFormFactory $trainingDateFormFactory,
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
