<?php
namespace MichalSpacekCz\Form;

/**
 * Talk slide form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TalkSlide extends ProtectedForm
{

	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent, $name)
	{
		parent::__construct($parent, $name);

		$pattern = '[a-z0-9-]+';
		$this->addText('alias', 'Alias:')
			->setRequired('Zadejte prosím alias')
			->addRule(self::PATTERN, 'Alias musí být ve formátu ' . $pattern, $pattern);
		$this->addText('number', 'Slajd:')
			->setType('number')
			->setAttribute('class', 'right')
			->setRequired('Zadejte prosím číslo slajdu');
		$this->addSubmit('submit', 'Přidat');
	}

}
