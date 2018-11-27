<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use MichalSpacekCz\Form\ChangePassword;
use MichalSpacekCz\Form\RegenerateTokens;

/**
 * User presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class UserPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\User\Manager */
	protected $authenticator;

	/** @var \Nette\Http\Session */
	protected $sessionHandler;


	/**
	 * @param \MichalSpacekCz\User\Manager $authenticator
	 * @param \Nette\Http\Session $sessionHandler
	 */
	public function __construct(\MichalSpacekCz\User\Manager $authenticator, \Nette\Http\Session $sessionHandler)
	{
		$this->authenticator = $authenticator;
		$this->sessionHandler = $sessionHandler;
		parent::__construct();
	}


	public function actionChangePassword(): void
	{
		$this->template->pageTitle = 'Změnit heslo';
	}


	protected function createComponentChangePassword(string $formName): ChangePassword
	{
		$form = new ChangePassword($this, $formName);
		$form->onSuccess[] = [$this, 'submittedChangePassword'];
		return $form;
	}


	public function submittedChangePassword(ChangePassword $form, $values): void
	{
		$this->authenticator->changePassword($this->user, $values->password, $values->newPassword);
		$this->redirect('Homepage:');
	}


	public function actionRegenerateTokens(): void
	{
		$this->template->pageTitle = 'Přegenerovat tokeny';
	}


	protected function createComponentRegenerateTokens(string $formName): RegenerateTokens
	{
		$form = new RegenerateTokens($this, $formName);
		$form->onSuccess[] = [$this, 'submittedRegenerateTokens'];
		return $form;
	}


	public function submittedRegenerateTokens(RegenerateTokens $form, \Nette\Utils\ArrayHash $values): void
	{
		if ($values->session) {
			$this->sessionHandler->regenerateId();
		}
		if ($values->permanent) {
			$this->authenticator->regeneratePermanentLogin($this->user);
		}
		if ($values->returning) {
			$this->authenticator->regenerateReturningUser($this->user);
		}
		$this->redirect('Homepage:');
	}

}
