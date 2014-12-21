<?php
namespace MichalSpacekCz\Form;

/**
 * Training statuses form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingStatuses extends Form
{

	public function __construct(\Nette\ComponentModel\IContainer $parent, $name, array $applications)
	{
		parent::__construct($parent, $name);
		$this->addProtection('Platnost formuláře vypršela, odešlete jej znovu');

		$container = $this->addContainer('applications');

		foreach ($applications as $application) {
			$select = $container->addSelect($application->id, 'Status')
				->setPrompt('- změnit na -')
				->setItems($application->childrenStatuses, false);
			if (empty($application->childrenStatuses)) {
				$select->setDisabled()
					->setPrompt('nelze dále měnit');
			}
		}
		$this->addStatusDate('date', 'Datum:', true);
		$this->addSubmit('submit', 'Změnit');
	}

}
