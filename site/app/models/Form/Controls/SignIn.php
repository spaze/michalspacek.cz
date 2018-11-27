<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

/**
 * Sign-in form trait.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
trait SignIn
{

	protected function addSignIn(\Nette\Forms\Container $container): void
	{
		$container->addText('username', 'Uživatel:')
			->setRequired('Zadejte prosím uživatele');
		$container->addPassword('password', 'Heslo:')
			->setRequired('Zadejte prosím heslo');
		$container->addCheckbox('remember', 'Zůstat přihlášen');
		$container->addSubmit('signin', 'Přihlásit');
	}

}
