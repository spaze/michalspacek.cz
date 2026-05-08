<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\Controls\PasskeyFormControls;
use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyException;
use MichalSpacekCz\User\WebAuthn\PasskeyCredentials;
use MichalSpacekCz\User\WebAuthn\WebAuthnAuthenticator;
use Nette\Http\IRequest;
use Nette\Security\User;
use Tracy\Debugger;

final readonly class PasskeyRegisterFormFactory
{

	public function __construct(
		private Manager $authenticator,
		private FormFactory $factory,
		private WebAuthnAuthenticator $passkeyAuthenticator,
		private PasskeyCredentials $passkeyCredentials,
		private PasskeyFormControls $passkeyFormControls,
		private IRequest $httpRequest,
		private Translator $translator,
	) {
	}


	/**
	 * @param callable(): void $onSuccess
	 */
	public function createRegisterForm(callable $onSuccess, User $user, string $errorUrl, string $canceledUrl, string $notSupportedUrl, ?string $options = null): UiForm
	{
		$form = $this->factory->create();
		if ($options !== null) {
			$form->setHtmlAttribute('data-options', $options);
		}
		$form->setHtmlAttribute('data-error-url', $errorUrl);
		$form->setHtmlAttribute('data-canceled-url', $canceledUrl);
		$form->setHtmlAttribute('data-not-supported-url', $notSupportedUrl);
		$this->passkeyFormControls->addRegistrationFields($form);
		$form->onSuccess[] = function (UiForm $form) use ($onSuccess, $user): void {
			$values = $form->getFormValues();
			assert(is_string($values->credential));
			assert(is_string($values->name));
			// Both the id and the user handle are read at form submission time so the credential is saved under whoever is actually logged in,
			// not whoever has created the form - in reality it will be the same user, but let's be sure
			$userId = (int)$user->getId();
			$userHandle = $this->passkeyCredentials->getUserHandle($userId);
			$username = $this->authenticator->getIdentityUsernameByUser($user);
			try {
				$this->passkeyAuthenticator->verifyRegistration($values->credential, $values->name, $userId, $userHandle);
				Debugger::log("Successful passkey registration ({$username}, {$this->httpRequest->getRemoteAddress()})", 'auth');
				$onSuccess();
			} catch (PasskeyException $e) {
				Debugger::log("Failed passkey registration: {$e->getMessage()} ({$this->httpRequest->getRemoteAddress()})", 'auth');
				$form->addError($this->translator->translate('messages.passkeys.registrationFailed'));
			}
		};
		return $form;
	}

}
