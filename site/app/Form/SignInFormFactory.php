<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\FormControlsFactory;
use MichalSpacekCz\User\Manager;
use Nette\Application\UI\Form;
use Nette\Http\IRequest;
use Nette\Security\AuthenticationException;
use Nette\Security\User;
use stdClass;
use Tracy\Debugger;

class SignInFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly FormControlsFactory $controlsFactory,
		private readonly User $user,
		private readonly Manager $authenticator,
		private readonly IRequest $httpRequest,
	) {
	}


	/**
	 * @param callable(): void $onSuccess
	 * @return Form
	 */
	public function create(callable $onSuccess): Form
	{
		$form = $this->factory->create();
		$this->controlsFactory->addSignIn($form);
		$form->onSuccess[] = function (Form $form, stdClass $values) use ($onSuccess): void {
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
