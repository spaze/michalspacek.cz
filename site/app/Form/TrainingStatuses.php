<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Nette\ComponentModel\IContainer;
use Nette\Database\Row;

class TrainingStatuses extends ProtectedForm
{

	/**
	 * @param IContainer $parent
	 * @param string $name
	 * @param Row[] $applications
	 * @param TrainingControlsFactory $trainingControlsFactory
	 */
	public function __construct(IContainer $parent, string $name, array $applications, TrainingControlsFactory $trainingControlsFactory)
	{
		parent::__construct($parent, $name);

		$container = $this->addContainer('applications');
		foreach ($applications as $application) {
			$select = $container->addSelect((string)$application->id, 'Status')
				->setPrompt('- změnit na -')
				->setItems($application->childrenStatuses, false);
			if (empty($application->childrenStatuses)) {
				$select->setDisabled()
					->setPrompt('nelze dále měnit');
			}
		}
		$trainingControlsFactory->addStatusDate($this, 'date', 'Datum:', true);
		$this->addSubmit('submit', 'Změnit');
		$this->addSubmit('familiar', 'Tykat všem')->setValidationScope([]);
	}

}
