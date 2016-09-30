<?php
namespace App\AdminModule\Presenters;

use Nette\Application\UI\Form;

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
	}


	public function actionChangePassword()
	{
		$this->template->pageTitle = 'Změnit heslo';
	}


	protected function createComponentChangePassword($formName)
	{
		$form = new \MichalSpacekCz\Form\ChangePassword($this, $formName);
		$form->onSuccess[] = [$this, 'submittedChangePassword'];
		return $form;
	}


	public function submittedChangePassword(\MichalSpacekCz\Form\ChangePassword $form, $values)
	{
		$this->authenticator->changePassword($this->user, $values->password, $values->newPassword);
		$this->redirect('Homepage:');
	}

}
