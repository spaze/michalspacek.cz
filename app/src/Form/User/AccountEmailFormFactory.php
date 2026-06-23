<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\Controls\PasskeyAuthenticationControls;
use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\User\UserAccounts;
use Nette\Application\UI\Form;
use Nette\Security\User;

final readonly class AccountEmailFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private UserAccounts $userAccounts,
		private User $user,
		private Translator $translator,
		private PasskeyAuthenticationControls $passkeyAuthenticationControls,
	) {
	}


	/**
	 * @param callable(): void $onSuccess
	 */
	public function create(callable $onSuccess): Form
	{
		$form = $this->factory->create();
		$email = $form->addEmail('email', $this->translator->translate('messages.account.email.label'))
			->setRequired($this->translator->translate('messages.account.email.required'));
		$currentEmail = $this->userAccounts->getEmail((int)$this->user->getId());
		if ($currentEmail !== null) {
			$email->setDefaultValue($currentEmail);
		}
		$form->addSubmit('save', $this->translator->translate('messages.account.email.save'));
		$form->setHtmlAttribute('data-error-element', 'passkeyReauthError');
		$this->passkeyAuthenticationControls->addReauthTo($form);
		$form->onSuccess[] = function (Form $form) use ($onSuccess): void {
			$values = $form->getValues();
			assert(is_string($values->email));
			$this->userAccounts->setEmail((int)$this->user->getId(), $values->email);
			$onSuccess();
		};
		return $form;
	}

}
