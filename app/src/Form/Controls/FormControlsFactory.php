<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

use Nette\Forms\Container;

final class FormControlsFactory
{

	public function addSignIn(Container $container): void
	{
		$container->addText('username', 'Uživatel:')
			->setHtmlAttribute('autocomplete', 'username')
			->setRequired('Zadejte prosím uživatele');
		$container->addPassword('password', 'Heslo:')
			->setHtmlAttribute('autocomplete', 'current-password')
			->setRequired('Zadejte prosím heslo');
		$container->addCheckbox('remember', 'Zůstat přihlášen');
		$container->addSubmit('signin', 'Přihlásit');
	}

}
