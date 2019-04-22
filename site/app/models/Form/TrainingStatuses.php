<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingStatusDate;
use Nette\ComponentModel\IContainer;
use Nette\Database\Row;
use Nette\Localization\ITranslator;

class TrainingStatuses extends ProtectedForm
{

	use TrainingStatusDate;

	/** @var ITranslator */
	protected $translator;


	/**
	 * @param IContainer $parent
	 * @param string $name
	 * @param Row[] $applications
	 * @param ITranslator $translator
	 */
	public function __construct(IContainer $parent, string $name, array $applications, ITranslator $translator)
	{
		parent::__construct($parent, $name);
		$this->translator = $translator;

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
		$this->addStatusDate('date', 'Datum:', true);
		$this->addSubmit('submit', 'Změnit');
		$this->addSubmit('familiar', 'Tykat všem')->setValidationScope([]);
	}

}
