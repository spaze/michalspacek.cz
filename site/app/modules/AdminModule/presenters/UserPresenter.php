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


	/**
	 * @param \MichalSpacekCz\UserManager $authenticator
	 */
	public function __construct(\MichalSpacekCz\UserManager $authenticator)
	{
		$this->authenticator = $authenticator;
		parent::__construct();
	}


	public function actionChangePassword()
	{
		$this->template->pageTitle = 'Změnit heslo';
	}


	protected function createComponentChangePassword($formName)
	{
		$form = new Form($this, $formName);

		$form->addPassword('password', 'Současné heslo:')
			->setRequired('Zadejte prosím současné heslo');
		$form->addPassword('newPassword', 'Nové heslo:')
			->setRequired('Zadejte prosím nové heslo')
			->addRule(Form::MIN_LENGTH, 'Nové heslo musí mít alespoň %d znaků', 6);
		$form->addPassword('newPasswordVerify', 'Nové heslo pro kontrolu:')
			->setRequired('Zadejte prosím nové heslo pro kontrolu')
			->addRule(Form::EQUAL, 'Hesla se neshodují', $form['newPassword']);
		$form->addSubmit('save', 'Uložit');
		$form->onSuccess[] = $this->submittedChangePassword;

		return $form;
	}


	public function submittedChangePassword($form)
	{
		$values = $form->getValues();

		$this->authenticator->changePassword($this->user->getIdentity()->username, $values['password'], $values['newPassword']);
		$this->redirect('Homepage:');
	}


}
