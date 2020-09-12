<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use MichalSpacekCz\Form\ChangePassword;
use MichalSpacekCz\Form\RegenerateTokens;
use MichalSpacekCz\User\Manager;
use Nette\Application\LinkGenerator;
use Nette\Forms\Form;
use Nette\Http\Session;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;

class UserPresenter extends BasePresenter
{

	/** @var Manager */
	protected $authenticator;

	/** @var Session */
	protected $sessionHandler;

	/** @var LinkGenerator */
	private LinkGenerator $linkGenerator;


	public function __construct(Manager $authenticator, Session $sessionHandler, LinkGenerator $linkGenerator)
	{
		$this->authenticator = $authenticator;
		$this->sessionHandler = $sessionHandler;
		$this->linkGenerator = $linkGenerator;
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


	/**
	 * @param Form $form
	 * @param ArrayHash<integer|string> $values
	 */
	public function submittedChangePassword(Form $form, ArrayHash $values): void
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


	/**
	 * @param Form $form
	 * @param ArrayHash<integer|string> $values
	 */
	public function submittedRegenerateTokens(Form $form, ArrayHash $values): void
	{
		if ($values->session) {
			$this->sessionHandler->regenerateId();
		}
		if ($values->permanent) {
			$this->authenticator->regeneratePermanentLogin($this->user);
		}
		if ($values->returning) {
			$selectorToken = $this->authenticator->regenerateReturningUser($this->user);
		}
		$message = Html::el()->setText('Tokeny přegenerovány ');
		if (isset($selectorToken)) {
			$message->addHtml(Html::el('a')->href($this->linkGenerator->link('Admin:Sign:knockKnock', [$selectorToken]))->setText('Odkaz na přihlášení'));
		}
		$this->flashMessage($message);
		$this->redirect('Homepage:');
	}

}
