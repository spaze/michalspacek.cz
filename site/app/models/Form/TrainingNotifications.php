<?php
namespace MichalSpacekCz\Form;

use MichalSpacekCz\Training;

/**
 * Notifications to send form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingNotifications extends \Nette\Application\UI\Form
{

	public function __construct(\Nette\ComponentModel\IContainer $parent, $name, array $applications)
	{
		parent::__construct($parent, $name);
		$this->addProtection('Platnost formuláře vypršela, odešlete jej znovu');

		$applicationsContainer = $this->addContainer('applications');

		foreach ($applications as $application) {
			$checked = 0;
			$applicationsContainer
				->addCheckbox($application->id)
				->setDefaultValue(isset($application->paid) && ++$checked <= 10);
		}

		$this->addSubmit('submit', 'Odeslat');
	}

}
