<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\Controls\PasskeyFormControls;
use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyException;
use MichalSpacekCz\User\WebAuthn\Registration\PasskeyRegistration;
use Nette\Forms\Form;
use Nette\Http\IRequest;
use Tracy\Debugger;

final readonly class PasskeyRegistrationFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private PasskeyRegistration $passkeyRegistration,
		private PasskeyFormControls $passkeyFormControls,
		private IRequest $httpRequest,
		private Translator $translator,
	) {
	}


	/**
	 * @param callable(bool $otherAccessRevokeFailed): void $onSuccess
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
				$result = $this->passkeyRegistration->register($values->credential, $values->name, $values->token);
			} catch (PasskeyException $e) {
				Debugger::log("Failed passkey registration: {$e->getMessage()} ({$this->httpRequest->getRemoteAddress()})", 'auth');
				$form->addError($this->translator->translate('messages.passkeys.registrationFailed'));
				return;
			}
			Debugger::log("Successful passkey registration ({$result->username}, {$this->httpRequest->getRemoteAddress()})", 'auth');
			if ($result->revokeFailure !== null) {
				Debugger::log("Revoking other access after reset failed: {$result->revokeFailure->getMessage()} ({$result->username})", 'auth');
			}
			$onSuccess($result->revokeFailure !== null);
		};
		return $form;
	}

}
