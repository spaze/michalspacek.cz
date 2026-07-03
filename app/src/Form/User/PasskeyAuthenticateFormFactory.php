<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\Controls\PasskeyAuthenticationControls;
use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\User\PermanentLogin\PermanentLogin;
use MichalSpacekCz\User\SecurityActivity\SecurityEventLogger;
use MichalSpacekCz\User\SecurityActivity\SecurityEventType;
use MichalSpacekCz\User\WebAuthn\Authentication\Reauthentication;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyServerException;
use MichalSpacekCz\User\WebAuthn\WebAuthnAuthenticator;
use Nette\Application\UI\Form;
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
		private Translator $translator,
		private Reauthentication $reauthentication,
		private SecurityEventLogger $securityEventLogger,
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
				$this->permanentLogin->regenerate();
				// Signing in with a passkey also counts as confirming identity, so sensitive actions won't immediately ask again.
				$this->reauthentication->recordFreshAuth();
				$this->securityEventLogger->record($result->userId, SecurityEventType::SignInSuccess, ['user' => $result->username, 'passkey' => $result->credentialName]);
				$onSuccess();
			} catch (PasskeyException $e) {
				// A wrong/unknown passkey is the user's doing and needs no record; only our own faults do.
				if ($e instanceof PasskeyServerException) {
					Debugger::log($e, 'auth');
				}
				$form->addError($this->translator->translate('messages.passkeys.authenticationFailed'));
			}
		};
		return $form;
	}

}
