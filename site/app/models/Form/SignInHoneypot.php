<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

/**
 * Sign-in form with no CSRF protection.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class SignInHoneypot extends UnprotectedForm
{

	use Controls\SignIn;


	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent, $name)
	{
		parent::__construct($parent, $name);
		$this->addSignIn($this);
	}

}
