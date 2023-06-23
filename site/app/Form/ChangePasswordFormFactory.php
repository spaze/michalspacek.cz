<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\User\Exceptions\IdentityNotSimpleIdentityException;
use MichalSpacekCz\User\Manager;
use Nette\Application\UI\Form;
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
	 * @return Form
	 * @throws IdentityNotSimpleIdentityException
	 */
	public function create(callable $onSuccess): Form
	{
		$form = $this->factory->create();
		$form->addText('username')
			->setDefaultValue($this->authenticator->getIdentityByUser($this->user)->username)
			->setHtmlAttribute('autocomplete', 'username')
			->setHtmlAttribute('class', 'hidden');
		$form->addPassword('password', 'Současné heslo:')
			->setHtmlAttribute('autocomplete', 'current-password')
			->setRequired('Zadejte prosím současné heslo');
		$newPassword = $form->addPassword('newPassword', 'Nové heslo:')
			->setHtmlAttribute('autocomplete', 'new-password')
			->setHtmlAttribute('passwordrules', 'minlength: 42; required: lower; required: upper; required: digit; required: [ !#$%&*+,./:;=?@_~];')
			->setRequired('Zadejte prosím nové heslo')
			->addRule($form::MIN_LENGTH, 'Nové heslo musí mít alespoň %d znaků', 15);
		$form->addPassword('newPasswordVerify', 'Nové heslo pro kontrolu:')
			->setHtmlAttribute('autocomplete', 'new-password')
			->setRequired('Zadejte prosím nové heslo pro kontrolu')
			->addRule($form::EQUAL, 'Hesla se neshodují', $newPassword);
		$form->addSubmit('save', 'Uložit');

		$form->onSuccess[] = function (Form $form) use ($onSuccess): void {
			$values = $form->getValues();
			$this->authenticator->changePassword($this->user, $values->password, $values->newPassword);
			$onSuccess();
		};

		return $form;
	}

}
