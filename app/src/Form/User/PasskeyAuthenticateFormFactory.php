<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\Controls\PasskeyAuthenticationControls;
use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\User\PermanentLogin\PermanentLogin;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyException;
use MichalSpacekCz\User\WebAuthn\WebAuthnAuthenticator;
use Nette\Application\UI\Form;
use Nette\Http\IRequest;
use Nette\Security\User;
use Tracy\Debugger;

final readonly class PasskeyAuthenticateFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private PasskeyAuthenticationControls $passkeyAuthenticationControls,
		private WebAuthnAuthenticator $passkeyAuthenticator,
		private Manager $authenticator,
		private PermanentLogin $permanentLogin,
		private User $user,
		private IRequest $httpRequest,
		private Translator $translator,
	) {
	}


	/**
	 * @param callable(): void $onSuccess
	 */
	public function create(callable $onSuccess, string $errorUrl, string $canceledUrl): Form
	{
		$form = $this->factory->create();
		$this->passkeyAuthenticationControls->addOptionsTo($form);
		$form->setHtmlAttribute('data-error-url', $errorUrl);
		$form->setHtmlAttribute('data-canceled-url', $canceledUrl);
		$form->addSubmit('authenticate');
		$form->onSuccess[] = function (Form $form) use ($onSuccess): void {
			$values = $form->getValues();
			assert(is_string($values->credential));
			try {
				$result = $this->passkeyAuthenticator->verifyAuthentication($values->credential);
				$this->user->setExpiration('30 minutes', true);
				$this->user->login($this->authenticator->getIdentity($result->userId, $result->username));
				$this->permanentLogin->regenerate($this->user);
				Debugger::log("Successful passkey sign-in ({$result->username}, {$this->httpRequest->getRemoteAddress()})", 'auth');
				$onSuccess();
			} catch (PasskeyException $e) {
				Debugger::log("Failed passkey sign-in: {$e->getMessage()} ({$this->httpRequest->getRemoteAddress()})", 'auth');
				$form->addError($this->translator->translate('messages.passkeys.authenticationFailed'));
			}
		};
		return $form;
	}

}
