<?php
namespace MichalSpacekCz\Form;

/**
 * Training file form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingFile extends \Nette\Application\UI\Form
{

	public function __construct(\Nette\ComponentModel\IContainer $parent, $name)
	{
		parent::__construct($parent, $name);
		$this->addUpload('file', 'Soubor:');
		$this->addSubmit('submit', 'Přidat');
	}

}
