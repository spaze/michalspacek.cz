<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

/**
 * Training statuses form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingStatuses extends ProtectedForm
{

	use Controls\TrainingStatusDate;

	/** @var \Nette\Localization\ITranslator */
	protected $translator;


	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 * @param \Nette\Database\Row[] $applications
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent, string $name, array $applications, \Nette\Localization\ITranslator $translator)
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
