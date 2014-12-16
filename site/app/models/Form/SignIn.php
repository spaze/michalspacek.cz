<?php
namespace MichalSpacekCz\Form;

/**
 * Sign-in form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class SignIn extends \Nette\Application\UI\Form
{

	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent, $name)
	{
		parent::__construct($parent, $name);
		$this->addText('username', 'Uživatel:')
			->setRequired('Zadejte prosím uživatele');
		$this->addPassword('password', 'Heslo:')
			->setRequired('Zadejte prosím heslo');
		$this->addCheckbox('remember', 'Zůstat přihlášen');
		$this->addSubmit('signin', 'Přihlásit');
	}

}
