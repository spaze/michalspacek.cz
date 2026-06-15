<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\Controls\PasskeyFormControls;
use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyException;
use MichalSpacekCz\User\WebAuthn\PasskeyRegistration;
use MichalSpacekCz\User\WebAuthn\WebAuthnAuthenticator;
use Nette\Forms\Form;
use Nette\Http\IRequest;
use Tracy\Debugger;

final readonly class PasskeyRegistrationFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private WebAuthnAuthenticator $passkeyAuthenticator,
		private PasskeyRegistration $passkeyRegistration,
		private PasskeyFormControls $passkeyFormControls,
		private IRequest $httpRequest,
		private Translator $translator,
	) {
	}


	/**
	 * @param callable(): void $onSuccess
	 */
	public function create(callable $onSuccess, string $optionsUrl, string $errorUrl, string $canceledUrl, string $notSupportedUrl): Form
	{
		$form = $this->factory->create();
		$form->setHtmlAttribute('id', 'passkeyRegistration'); // passkey-register.js finds the form by this id
		$form->setHtmlAttribute('data-options-url', $optionsUrl);
		$form->setHtmlAttribute('data-error-url', $errorUrl);
		$form->setHtmlAttribute('data-canceled-url', $canceledUrl);
		$form->setHtmlAttribute('data-not-supported-url', $notSupportedUrl);
		$this->passkeyFormControls->addRegistrationFields($form, $this->translator->translate('messages.passkeys.loadingOptions'));
		$form->addHidden('token')
			->setRequired();
		$form->onSuccess[] = function (Form $form) use ($onSuccess): void {
			$values = $form->getValues();
			assert(is_string($values->credential));
			assert(is_string($values->name));
			assert(is_string($values->token));
			try {
				$userAuthToken = $this->passkeyRegistration->getUserAuthToken($values->token);
				$this->passkeyRegistration->cleanupToken($userAuthToken);
				$this->passkeyAuthenticator->verifyRegistration($values->credential, $values->name, $userAuthToken->getUserId());
				Debugger::log("Successful passkey registration ({$userAuthToken->getUsername()}, {$this->httpRequest->getRemoteAddress()})", 'auth');
				$onSuccess();
			} catch (PasskeyException $e) {
				Debugger::log("Failed passkey registration: {$e->getMessage()} ({$this->httpRequest->getRemoteAddress()})", 'auth');
				$form->addError($this->translator->translate('messages.passkeys.registrationFailed'));
			}
		};
		return $form;
	}

}
