<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

/**
 * Abstract form with CSRF protection.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
abstract class ProtectedForm extends \Nette\Application\UI\Form
{

	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent, string $name)
	{
		parent::__construct($parent, $name);
		$this->addProtection('Platnost formuláře vypršela, odešlete jej znovu');
	}

}
