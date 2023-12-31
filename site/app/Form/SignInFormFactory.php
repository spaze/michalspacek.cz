<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\FormControlsFactory;
use MichalSpacekCz\User\Manager;
use Nette\Http\IRequest;
use Nette\Security\AuthenticationException;
use Nette\Security\User;
use Tracy\Debugger;

readonly class SignInFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private FormControlsFactory $controlsFactory,
		private User $user,
		private Manager $authenticator,
		private IRequest $httpRequest,
	) {
	}


	/**
	 * @param callable(): void $onSuccess
	 */
	public function create(callable $onSuccess): UiForm
	{
		$form = $this->factory->create();
		$this->controlsFactory->addSignIn($form);
		$form->onSuccess[] = function (UiForm $form) use ($onSuccess): void {
			$values = $form->getFormValues();
			$this->user->setExpiration('30 minutes', true);
			try {
				$this->user->login($values->username, $values->password);
				Debugger::log("Successful sign-in attempt ({$values->username}, {$this->httpRequest->getRemoteAddress()})", 'auth');
				if ($values->remember) {
					$this->authenticator->storePermanentLogin($this->user);
				} else {
					$this->authenticator->clearPermanentLogin($this->user);
				}
				$onSuccess();
			} catch (AuthenticationException $e) {
				Debugger::log("Failed sign-in attempt: {$e->getMessage()} ({$values->username}, {$this->httpRequest->getRemoteAddress()})", 'auth');
				$form->addError('Špatné uživatelské jméno nebo heslo');
			}
		};
		return $form;
	}

}
