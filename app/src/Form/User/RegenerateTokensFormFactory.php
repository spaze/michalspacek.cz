<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\User\PermanentLogin\PermanentLogin;
use Nette\Http\Session;
use Nette\Security\User;
use Nette\Utils\Html;

final readonly class RegenerateTokensFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private Session $sessionHandler,
		private PermanentLogin $permanentLogin,
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
		$form->addSubmit('regenerate', 'Přegenerovat');

		$form->onSuccess[] = function (UiForm $form) use ($onSuccess): void {
			$values = $form->getFormValues();
			if ($values->session) {
				$this->sessionHandler->regenerateId();
			}
			if ($values->permanent) {
				$this->permanentLogin->regenerate($this->user);
			}
			$onSuccess('Tokeny přegenerovány');
		};
		return $form;
	}

}
