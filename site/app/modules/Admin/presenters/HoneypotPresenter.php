<?php
namespace App\AdminModule\Presenters;

/**
 * Honeypot presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HoneypotPresenter extends \App\Presenters\BasePresenter
{

	public function actionSignIn()
	{
		$session = $this->getSession()->start();
		$this->template->pageTitle = 'Přihlásit se';
	}


	protected function createComponentSignIn($formName)
	{
		$form = new \MichalSpacekCz\Form\SignIn($this, $formName);
		$form->onSuccess[] = [$this, 'submittedSignIn'];
		return $form;
	}


	public function submittedSignIn(\MichalSpacekCz\Form\SignIn $form, $values)
	{
		\Tracy\Debugger::log("Sign-in attempt: {$values->username}, {$values->password}, {$this->getHttpRequest()->getRemoteAddress()}", 'honeypot');
		$form->addError('Špatné uživatelské jméno nebo heslo');
	}

}
