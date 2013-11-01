<?php
namespace MichalSpacekCz\Form;

/**
 * E-mails to send form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingMailsOutbox extends \Nette\Application\UI\Form
{


	public function __construct(\Nette\ComponentModel\IContainer $parent, $name, array $applications)
	{
		parent::__construct($parent, $name);
	
		$container = $this->addContainer('applications');

		foreach ($applications as $application) {
			$container->addCheckbox($application->id)
				->setDefaultValue(true);
		}

		$this->addSubmit('submit', 'Odeslat');
	}


}