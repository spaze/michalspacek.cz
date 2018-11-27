<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use MichalSpacekCz\Form\ChangePassword;

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


	/**
	 * @param \MichalSpacekCz\User\Manager $authenticator
	 */
	public function __construct(\MichalSpacekCz\User\Manager $authenticator)
	{
		$this->authenticator = $authenticator;
		parent::__construct();
	}


	public function actionChangePassword(): void
	{
		$this->template->pageTitle = 'Změnit heslo';
	}


	protected function createComponentChangePassword($formName): ChangePassword
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

}
