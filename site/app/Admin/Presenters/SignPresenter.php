<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Form\SignIn;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\Www\Presenters\BasePresenter;
use Nette\Forms\Form;
use Nette\Security\AuthenticationException;
use Nette\Utils\ArrayHash;
use Tracy\Debugger;

class SignPresenter extends BasePresenter
{

	/** @persistent */
	public string $backlink = '';

	private Manager $authenticator;


	public function __construct(Manager $authenticator)
	{
		$this->authenticator = $authenticator;
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$this->redirect('in');
	}


	public function actionKnockKnock(string $param): void
	{
		if ($this->authenticator->verifyReturningUser($param)) {
			$this->authenticator->setReturningUser($param);
		}

		$this->redirect($this->user->isLoggedIn() ? 'Homepage:' : 'in');
	}


	public function actionIn(): void
	{
		if (!$this->authenticator->isReturningUser()) {
			$this->forward('Honeypot:signIn');
		}

		$this->getSession()->start();
		$token = $this->authenticator->verifyPermanentLogin();
		if ($token !== null) {
			$this->user->login($this->authenticator->getIdentity($token->userId, $token->username));
			$this->authenticator->regeneratePermanentLogin($this->user);
			$this->restoreRequest($this->backlink);
			$this->redirect('Homepage:');
		}
		$this->template->pageTitle = 'Přihlásit se';
	}


	protected function createComponentSignIn(string $formName): SignIn
	{
		$form = new SignIn($this, $formName);
		$form->onSuccess[] = [$this, 'submittedSignIn'];
		return $form;
	}


	/**
	 * @param Form $form
	 * @param ArrayHash<integer|string> $values
	 */
	public function submittedSignIn(Form $form, ArrayHash $values): void
	{
		$this->user->setExpiration('30 minutes', true);
		try {
			$this->user->login($values->username, $values->password);
			Debugger::log("Successful sign-in attempt ({$values->username}, {$this->getHttpRequest()->getRemoteAddress()})", 'auth');
			if ($values->remember) {
				$this->authenticator->storePermanentLogin($this->user);
			} else {
				$this->authenticator->clearPermanentLogin($this->user);
			}
			$this->restoreRequest($this->backlink);
			$this->redirect('Homepage:');
		} catch (AuthenticationException $e) {
			Debugger::log("Failed sign-in attempt: {$e->getMessage()} ({$values->username}, {$this->getHttpRequest()->getRemoteAddress()})", 'auth');
			$form->addError('Špatné uživatelské jméno nebo heslo');
		}
	}


	public function actionOut(): void
	{
		$this->authenticator->clearPermanentLogin($this->user);
		$this->user->logout();
		$this->flashMessage('Byli jste odhlášeni');
		$this->redirect('in');
	}

}
