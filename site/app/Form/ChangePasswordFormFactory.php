<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\User\Exceptions\IdentityException;
use MichalSpacekCz\User\Manager;
use Nette\Security\User;

class ChangePasswordFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly Manager $authenticator,
		private readonly User $user,
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
			->addRule($form::MinLength, 'Nové heslo musí mít alespoň %d znaků', 15);
		$form->addPassword('newPasswordVerify', 'Nové heslo pro kontrolu:')
			->setHtmlAttribute('autocomplete', 'new-password')
			->setRequired('Zadejte prosím nové heslo pro kontrolu')
			->addRule($form::Equal, 'Hesla se neshodují', $newPassword);
		$form->addSubmit('save', 'Uložit');

		$form->onSuccess[] = function (UiForm $form) use ($onSuccess): void {
			$values = $form->getFormValues();
			$this->authenticator->changePassword($this->user, $values->password, $values->newPassword);
			$onSuccess();
		};

		return $form;
	}

}
