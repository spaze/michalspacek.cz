<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\User\Exceptions\IdentityException;
use MichalSpacekCz\User\Manager;
use Nette\Forms\Form;
use Nette\Security\User;

final readonly class ChangePasswordFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private Manager $authenticator,
		private User $user,
	) {
	}


	/**
	 * @param callable(): void $onSuccess
	 * @throws IdentityException
	 */
	public function create(callable $onSuccess): UiForm
	{
		$form = $this->factory->create();
		$form->addText('username')
			->setDefaultValue($this->authenticator->getIdentityUsernameByUser($this->user))
			->setHtmlAttribute('autocomplete', 'username')
			->setHtmlAttribute('class', 'hidden');
		$form->addPassword('password', 'Současné heslo:')
			->setHtmlAttribute('autocomplete', 'current-password')
			->setRequired('Zadejte prosím současné heslo');
		$newPassword = $form->addPassword('newPassword', 'Nové heslo:')
			->setHtmlAttribute('autocomplete', 'new-password')
			->setHtmlAttribute('passwordrules', 'minlength: 42; required: lower; required: upper; required: digit; required: [ !#$%&*+,./:;=?@_~];')
			->setRequired('Zadejte prosím nové heslo')
			->addRule(Form::MinLength, 'Nové heslo musí mít alespoň %d znaků', 15);
		$form->addPassword('newPasswordVerify', 'Nové heslo pro kontrolu:')
			->setHtmlAttribute('autocomplete', 'new-password')
			->setRequired('Zadejte prosím nové heslo pro kontrolu')
			->addRule(Form::Equal, 'Hesla se neshodují', $newPassword);
		$form->addSubmit('save', 'Uložit');

		$form->onSuccess[] = function (UiForm $form) use ($onSuccess): void {
			$values = $form->getFormValues();
			assert(is_string($values->password));
			assert(is_string($values->newPassword));
			$this->authenticator->changePassword($this->user, $values->password, $values->newPassword);
			$onSuccess();
		};

		return $form;
	}

}
