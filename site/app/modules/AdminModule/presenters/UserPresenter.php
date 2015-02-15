<?php
namespace AdminModule;

use \Nette\Application\UI\Form;

/**
 * User presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class UserPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\UserManager */
	protected $authenticator;

	protected $trainingApplications;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\UserManager $authenticator
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		\MichalSpacekCz\UserManager $authenticator,
		\MichalSpacekCz\Training\Applications $trainingApplications
	)
	{
		$this->authenticator = $authenticator;
		$this->trainingApplications = $trainingApplications;
		parent::__construct($translator);
	}


	public function actionChangePassword()
	{
		$this->template->pageTitle = 'Změnit heslo';
	}


	protected function createComponentChangePassword($formName)
	{
		$form = new \MichalSpacekCz\Form\ChangePassword($this, $formName);
		$form->onSuccess[] = $this->submittedChangePassword;
		return $form;
	}


	public function submittedChangePassword(\MichalSpacekCz\Form\ChangePassword $form)
	{
		$values = $form->getValues();

		$this->authenticator->changePassword($this->user->getIdentity()->username, $values['password'], $values['newPassword']);
		$this->redirect('Homepage:');
	}


	public function actionDefault()
	{
		$this->authenticator->reEncryptPasswords();
		$this->trainingApplications->reEncryptEmails();
	}

}
