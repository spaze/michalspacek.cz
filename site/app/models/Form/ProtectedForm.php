<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Nette\Application\UI\Form;
use Nette\ComponentModel\IContainer;

/**
 * Abstract form with CSRF protection.
 */
abstract class ProtectedForm extends Form
{

	public function __construct(IContainer $parent, string $name)
	{
		parent::__construct($parent, $name);
		$this->addProtection('Platnost formuláře vypršela, odešlete jej znovu');
	}

}
