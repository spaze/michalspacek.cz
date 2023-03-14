<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\User\Manager;
use Nette\Application\UI\Form;
use Nette\Security\User;
use stdClass;

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
	 */
	public function create(callable $onSuccess): Form
	{
		$form = $this->factory->create();
		$form->addPassword('password', 'Současné heslo:')
			->setRequired('Zadejte prosím současné heslo');
		$newPassword = $form->addPassword('newPassword', 'Nové heslo:')
			->setRequired('Zadejte prosím nové heslo')
			->addRule($form::MIN_LENGTH, 'Nové heslo musí mít alespoň %d znaků', 15);
		$form->addPassword('newPasswordVerify', 'Nové heslo pro kontrolu:')
			->setRequired('Zadejte prosím nové heslo pro kontrolu')
			->addRule($form::EQUAL, 'Hesla se neshodují', $newPassword);
		$form->addSubmit('save', 'Uložit');

		$form->onSuccess[] = function (Form $form, stdClass $values) use ($onSuccess): void {
			$this->authenticator->changePassword($this->user, $values->password, $values->newPassword);
			$onSuccess();
		};

		return $form;
	}

}
