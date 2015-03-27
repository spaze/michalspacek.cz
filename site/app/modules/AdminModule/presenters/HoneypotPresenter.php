<?php
namespace AdminModule;

/**
 * Honeypot presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HoneypotPresenter extends \BasePresenter
{

	public function actionSignIn()
	{
		$this->template->pageTitle = 'Přihlásit se';
	}


	protected function createComponentSignIn($formName)
	{
		$form = new \MichalSpacekCz\Form\SignIn($this, $formName);
		$form->onSuccess[] = $this->submittedSignIn;
		return $form;
	}


	public function submittedSignIn(\MichalSpacekCz\Form\SignIn $form)
	{
		$values = $form->getValues();
		\Tracy\Debugger::log("Sign-in attempt: {$values->username}, {$values->password}, {$this->getHttpRequest()->getRemoteAddress()}", 'honeypot');
		$form->addError('Špatné uživatelské jméno nebo heslo');
	}

}
