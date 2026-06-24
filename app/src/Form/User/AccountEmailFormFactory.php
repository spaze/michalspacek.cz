<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\Controls\PasskeyAuthenticationControls;
use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\User\Notifications\UserSecurityNotifier;
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
		private UserSecurityNotifier $notifier,
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
		$userId = (int)$this->user->getId();
		$currentEmail = $this->userAccounts->getEmail($userId);
		if ($currentEmail !== null) {
			$email->setDefaultValue($currentEmail);
		}
		$form->addSubmit('save', $this->translator->translate('messages.account.email.save'));
		$form->setHtmlAttribute('data-error-element', 'passkeyReauthError');
		$this->passkeyAuthenticationControls->addReauthTo($form);
		$form->onSuccess[] = function (Form $form) use ($onSuccess, $userId, $currentEmail): void {
			$values = $form->getValues();
			assert(is_string($values->email));
			$this->userAccounts->setEmail($userId, $values->email);
			$this->notifier->emailChanged($currentEmail, $values->email);
			$onSuccess();
		};
		return $form;
	}

}
