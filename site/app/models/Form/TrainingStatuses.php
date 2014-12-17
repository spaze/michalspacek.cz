<?php
namespace MichalSpacekCz\Form;

/**
 * Training statuses form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingStatuses extends \Nette\Application\UI\Form
{

	public function __construct(\Nette\ComponentModel\IContainer $parent, $name, array $applications)
	{
		parent::__construct($parent, $name);

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
		$this->addText('date', 'Datum:')
			->setAttribute('placeholder', 'YYYY-MM-DD HH:MM:SS nebo DD.MM.YYYY HH:MM:SS nebo NOW')
			->setAttribute('title', 'Formát YYYY-MM-DD HH:MM:SS nebo DD.MM.YYYY HH:MM:SS nebo NOW')
			->addCondition(self::FILLED)
			->addRule(self::PATTERN, 'Datum musí být ve formátu YYYY-MM-DD HH:MM:SS nebo DD.MM.YYYY HH:MM:SS nebo NOW', '(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2}:\d{2})|[Nn][Oo][Ww]');
		$this->addSubmit('submit', 'Změnit');
	}

}
