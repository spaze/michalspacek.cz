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

	/** @var \MichalSpacekCz\UserManager */
	protected $authenticator;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\UserManager $authenticator
	 */
	public function __construct(\Nette\Localization\ITranslator $translator, \MichalSpacekCz\UserManager $authenticator)
	{
		$this->authenticator = $authenticator;
		parent::__construct($translator);
	}


	/**
	 * Port-knocking-like login.
	 */
	private function verify()
	{
		$this->authenticator->verifySignInAuthorization($this->getSession('admin')->knockKnock);
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
			$session->knockKnock = \MichalSpacekCz\UserManager::KNOCK_KNOCK;
			$this->redirect('in');
		}
	}


	public function actionIn()
	{
		$this->verify();
		$this->template->pageTitle = 'Přihlásit se';
	}


	protected function createComponentSignIn()
	{
		$form = new \Nette\Application\UI\Form;
		$form->addText('username', 'Uživatel:')
			->setRequired('Zadejte prosím uživatele');
		$form->addPassword('password', 'Heslo:')
			->setRequired('Zadejte prosím heslo');
		$form->addCheckbox('remember', 'Zůstat přihlášen');
		$form->addSubmit('signin', 'Přihlásit');

		$form->onSuccess[] = $this->submittedSignIn;
		return $form;
	}


	public function submittedSignIn($form)
	{
		$values = $form->getValues();

		if ($values->remember) {
			$this->user->setExpiration('14 days', false);
		} else {
			$this->user->setExpiration('30 minutes', true);
		}

		try {
			$this->user->login($values->username, $values->password);
			\Nette\Diagnostics\Debugger::log("Successful sign-in attempt ({$values->username}, {$this->getHttpRequest()->getRemoteAddress()})", 'auth');
			$this->redirect('Homepage:');
		} catch (\Nette\Security\AuthenticationException $e) {
			\Nette\Diagnostics\Debugger::log("Failed sign-in attempt: {$e->getMessage()} ({$values->username}, {$values->password}, {$this->getHttpRequest()->getRemoteAddress()})", 'auth');
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
