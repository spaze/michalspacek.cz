<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\User\PermanentLogin\PermanentLogin;
use Nette\Forms\Form;
use Nette\Http\Session;
use Nette\Utils\Html;

final readonly class RegenerateTokensFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private Session $sessionHandler,
		private PermanentLogin $permanentLogin,
	) {
	}


	/**
	 * @param callable(Html|string): void $onSuccess
	 */
	public function create(callable $onSuccess): Form
	{
		$form = $this->factory->create();
		$form->addCheckbox('session', 'Session id')->setDefaultValue(true);
		$form->addCheckbox('permanent', 'Permanent login token')->setDefaultValue(true);
		$form->addSubmit('regenerate', 'Přegenerovat');

		$form->onSuccess[] = function (Form $form) use ($onSuccess): void {
			$values = $form->getValues();
			if ($values->session) {
				$this->sessionHandler->regenerateId();
			}
			if ($values->permanent) {
				$this->permanentLogin->regenerate();
			}
			$onSuccess('Tokeny přegenerovány');
		};
		return $form;
	}

}
