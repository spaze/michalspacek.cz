<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Form\SignInFormFactory;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\Www\Presenters\BasePresenter;
use Nette\Http\Session;
use Nette\Security\User;

final class SignPresenter extends BasePresenter
{

	/** @persistent */
	public string $backlink = '';


	public function __construct(
		private readonly Manager $authenticator,
		private readonly SignInFormFactory $signInFormFactory,
		private readonly User $user,
		private readonly Session $sessionHandler,
	) {
		parent::__construct();
	}


	public function actionDefault(): never
	{
		$this->redirect('in');
	}


	public function actionKnockKnock(string $param): never
	{
		if ($this->authenticator->verifyReturningUser($param) !== null) {
			$this->authenticator->setReturningUser($param);
		}

		$this->redirect($this->user->isLoggedIn() ? 'Homepage:' : 'in');
	}


	public function actionIn(): void
	{
		if (!$this->authenticator->isReturningUser()) {
			$this->forward('Honeypot:signIn');
		}

		$this->sessionHandler->start();
		$token = $this->authenticator->verifyPermanentLogin();
		if ($token !== null) {
			$this->user->login($this->authenticator->getIdentity($token->getUserId(), $token->getUsername()));
			$this->authenticator->regeneratePermanentLogin($this->user);
			$this->restoreRequest($this->backlink);
			$this->redirect('Homepage:');
		}
		$this->template->pageTitle = 'Přihlásit se';
	}


	protected function createComponentSignIn(): UiForm
	{
		return $this->signInFormFactory->create(
			function (): never {
				$this->restoreRequest($this->backlink);
				$this->redirect('Homepage:');
			},
		);
	}


	public function actionOut(): never
	{
		$this->authenticator->clearPermanentLogin($this->user);
		$this->user->logout();
		$this->flashMessage('Byli jste odhlášeni');
		$this->redirect('in');
	}

}
