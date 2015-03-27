<?php
namespace AdminModule;

/**
 * Sign in/out presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class SignPresenter extends \BasePresenter
{

	/** @var \MichalSpacekCz\User\Manager */
	protected $authenticator;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\User\Manager $authenticator
	 */
	public function __construct(\Nette\Localization\ITranslator $translator, \MichalSpacekCz\User\Manager $authenticator)
	{
		$this->authenticator = $authenticator;
		parent::__construct($translator);
	}


	/**
	 * Port-knocking-like login.
	 */
	private function verify()
	{
		try {
			$this->authenticator->verifySignInAuthorization($this->getSession('admin')->knockKnock);
		} catch (\MichalSpacekCz\User\UnauthorizedSignInException $e) {
			$this->forward('Honeypot:signIn');
		}
	}


	public function actionDefault()
	{
		$this->verify();
		$this->redirect('in');
	}


	public function actionKnockKnock()
	{
		if ($this->user->isLoggedIn()) {
			$this->redirect('Homepage:');
		} else {
			$session = $this->getSession('admin');
			$session->knockKnock = \MichalSpacekCz\User\Manager::KNOCK_KNOCK;
			$this->redirect('in');
		}
	}


	public function actionIn()
	{
		$this->verify();
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

		if ($values->remember) {
			$this->user->setExpiration('14 days', false);
		} else {
			$this->user->setExpiration('30 minutes', true);
		}

		try {
			$this->user->login($values->username, $values->password);
			\Tracy\Debugger::log("Successful sign-in attempt ({$values->username}, {$this->getHttpRequest()->getRemoteAddress()})", 'auth');
			$this->redirect('Homepage:');
		} catch (\Nette\Security\AuthenticationException $e) {
			\Tracy\Debugger::log("Failed sign-in attempt: {$e->getMessage()} ({$values->username}, {$this->getHttpRequest()->getRemoteAddress()})", 'auth');
			$form->addError('Špatné uživatelské jméno nebo heslo');
		}
	}


	public function actionOut()
	{
		$this->verify();
		$this->user->logout();
		$this->flashMessage('Byli jste odhlášeni');
		$this->redirect('in');
	}

}
