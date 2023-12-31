<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\User\Manager;
use Nette\Application\LinkGenerator;
use Nette\Http\Session;
use Nette\Security\User;
use Nette\Utils\Html;

readonly class RegenerateTokensFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private Session $sessionHandler,
		private LinkGenerator $linkGenerator,
		private Manager $authenticator,
		private User $user,
	) {
	}


	/**
	 * @param callable(Html|string): void $onSuccess
	 */
	public function create(callable $onSuccess): UiForm
	{
		$form = $this->factory->create();
		$form->addCheckbox('session', 'Session id')->setDefaultValue(true);
		$form->addCheckbox('permanent', 'Permanent login token')->setDefaultValue(true);
		$form->addCheckbox('returning', 'Returning user token')->setDefaultValue(true);
		$form->addSubmit('regenerate', 'Přegenerovat');

		$form->onSuccess[] = function (UiForm $form) use ($onSuccess): void {
			$values = $form->getFormValues();
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
			$onSuccess($message);
		};
		return $form;
	}

}
