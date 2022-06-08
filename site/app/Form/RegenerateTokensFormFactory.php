<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\User\Manager;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\Form;
use Nette\Http\Session;
use Nette\Security\User;
use Nette\Utils\Html;
use stdClass;

class RegenerateTokensFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly Session $sessionHandler,
		private readonly LinkGenerator $linkGenerator,
		private readonly Manager $authenticator,
		private readonly User $user,
	) {
	}


	/**
	 * @param callable(Html|string): void $onSuccess
	 * @return Form
	 */
	public function create(callable $onSuccess): Form
	{
		$form = $this->factory->create();
		$form->addCheckbox('session', 'Session id')->setDefaultValue(true);
		$form->addCheckbox('permanent', 'Permanent login token')->setDefaultValue(true);
		$form->addCheckbox('returning', 'Returning user token')->setDefaultValue(true);
		$form->addSubmit('regenerate', 'Přegenerovat');

		$form->onSuccess[] = function (Form $form, stdClass $values) use ($onSuccess): void {
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
