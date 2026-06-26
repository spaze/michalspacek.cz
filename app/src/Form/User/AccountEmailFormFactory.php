<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\Controls\PasskeyAuthenticationControls;
use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\User\Notifications\UserSecurityNotifier;
use MichalSpacekCz\User\SecurityActivity\SecurityEventLogger;
use MichalSpacekCz\User\SecurityActivity\SecurityEventType;
use MichalSpacekCz\User\UserAccounts;
use MichalSpacekCz\User\WebAuthn\Authentication\ReauthKind;
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
		private SecurityEventLogger $securityEventLogger,
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
		$this->passkeyAuthenticationControls->addReauthTo($form, ReauthKind::Inline);
		$form->onSuccess[] = function (Form $form) use ($onSuccess, $userId): void {
			$values = $form->getValues();
			assert(is_string($values->email));
			$oldEmail = $this->userAccounts->changeEmail($userId, $values->email);
			if ($oldEmail !== $values->email) {
				$this->notifier->emailChanged($oldEmail, $values->email);
				$this->securityEventLogger->record($userId, SecurityEventType::EmailChanged, ['from' => $oldEmail, 'to' => $values->email]);
			}
			$onSuccess();
		};
		return $form;
	}

}
