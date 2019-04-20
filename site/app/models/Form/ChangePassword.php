<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

class ChangePassword extends ProtectedForm
{

	public function __construct(\Nette\ComponentModel\IContainer $parent, string $name)
	{
		parent::__construct($parent, $name);

		$this->addPassword('password', 'Současné heslo:')
			->setRequired('Zadejte prosím současné heslo');
		$this->addPassword('newPassword', 'Nové heslo:')
			->setRequired('Zadejte prosím nové heslo')
			->addRule(self::MIN_LENGTH, 'Nové heslo musí mít alespoň %d znaků', 6);
		$this->addPassword('newPasswordVerify', 'Nové heslo pro kontrolu:')
			->setRequired('Zadejte prosím nové heslo pro kontrolu')
			->addRule(self::EQUAL, 'Hesla se neshodují', $this->getComponent('newPassword'));
		$this->addSubmit('save', 'Uložit');
	}

}
