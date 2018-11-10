<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use MichalSpacekCz\Form\SignInHoneypot;

/**
 * Honeypot presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HoneypotPresenter extends \App\WwwModule\Presenters\BasePresenter
{

	public function actionSignIn(): void
	{
		$this->template->pageTitle = 'Přihlásit se';
	}


	protected function createComponentSignIn(string $formName): SignInHoneypot
	{
		$form = new SignInHoneypot($this, $formName);
		$form->onSuccess[] = [$this, 'submittedSignIn'];
		return $form;
	}


	public function submittedSignIn(SignInHoneypot $form, \Nette\Utils\ArrayHash $values): void
	{
		\Tracy\Debugger::log("Sign-in attempt: {$values->username}, {$values->password}, {$this->getHttpRequest()->getRemoteAddress()}", 'honeypot');
		$form->addError('Špatné uživatelské jméno nebo heslo');
	}

}
