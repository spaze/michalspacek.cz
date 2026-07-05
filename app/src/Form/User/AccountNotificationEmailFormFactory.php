<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\Controls\PasskeyAuthenticationControls;
use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\User\Exceptions\IdentityIdNotIntException;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\User\Notifications\UserSecurityNotifier;
use MichalSpacekCz\User\SecurityActivity\SecurityEventLogger;
use MichalSpacekCz\User\SecurityActivity\SecurityEventType;
use MichalSpacekCz\User\UserAccounts;
use MichalSpacekCz\User\WebAuthn\Authentication\ReauthKind;
use Nette\Application\UI\Form;
use Nette\Security\User;

final readonly class AccountNotificationEmailFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private UserAccounts $userAccounts,
		private User $user,
		private Manager $manager,
		private Translator $translator,
		private PasskeyAuthenticationControls $passkeyAuthenticationControls,
		private UserSecurityNotifier $notifier,
		private SecurityEventLogger $securityEventLogger,
	) {
	}


	/**
	 * @param callable(): void $onSuccess
	 * @throws IdentityIdNotIntException
	 */
	public function create(callable $onSuccess): Form
	{
		$form = $this->factory->create();
		$email = $form->addEmail('notificationEmail', $this->translator->translate('messages.account.notificationEmail.label'))
			->setRequired($this->translator->translate('messages.account.notificationEmail.required'));
		$userId = $this->manager->getUserId($this->user);
		$currentEmail = $this->userAccounts->getNotificationEmail($userId);
		if ($currentEmail !== null) {
			$email->setDefaultValue($currentEmail);
		}
		$form->addSubmit('save', $this->translator->translate('messages.account.notificationEmail.save'));
		$form->setHtmlAttribute('data-error-element', 'passkeyReauthError');
		$this->passkeyAuthenticationControls->addReauthTo($form, ReauthKind::Inline, SecurityEventType::NotificationEmailChanged);
		$form->onSuccess[] = function (Form $form) use ($onSuccess, $userId): void {
			$values = $form->getValues();
			assert(is_string($values->notificationEmail));
			$oldEmail = $this->userAccounts->changeNotificationEmail($userId, $values->notificationEmail);
			if ($oldEmail !== $values->notificationEmail) {
				$this->notifier->notificationEmailChanged($oldEmail, $values->notificationEmail);
				$this->securityEventLogger->record($userId, SecurityEventType::NotificationEmailChanged, ['from' => $oldEmail, 'to' => $values->notificationEmail]);
			}
			$onSuccess();
		};
		return $form;
	}

}
