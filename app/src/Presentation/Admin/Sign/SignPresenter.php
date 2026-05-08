<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Sign;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\Form\User\PasskeyAuthenticateFormFactory;
use MichalSpacekCz\Form\User\PasskeyResetFormFactory;
use MichalSpacekCz\Form\User\SignInHoneypotFormFactory;
use MichalSpacekCz\Http\HttpInput;
use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyDirective;
use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyOrigin;
use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetException;
use MichalSpacekCz\User\WebAuthn\PasskeyReset;
use MichalSpacekCz\User\WebAuthn\WebAuthnAuthenticator;
use Nette\Application\BadRequestException;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\Session;
use Nette\Security\User;

final class SignPresenter extends BasePresenter
{

	/** @persistent */
	public string $backlink = '';

	private ?string $passkeyAuthOptions = null;


	public function __construct(
		private readonly Manager $authenticator,
		private readonly SignInHoneypotFormFactory $signInHoneypotFormFactory,
		private readonly PasskeyAuthenticateFormFactory $passkeyAuthenticateFormFactory,
		private readonly PasskeyResetFormFactory $passkeyResetFormFactory,
		private readonly PasskeyReset $passkeyReset,
		private readonly WebAuthnAuthenticator $passkeyAuthenticator,
		private readonly HttpInput $httpInput,
		private readonly User $user,
		private readonly Session $sessionHandler,
		private readonly Translator $translator,
	) {
		parent::__construct();
	}


	public function actionDefault(): never
	{
		$this->redirect('in');
	}


	public function actionIn(): void
	{
		$this->addPermissionsPolicy(PermissionsPolicyDirective::PublicKeyCredentialsGet, PermissionsPolicyOrigin::Self);
		$this->sessionHandler->start();
		$token = $this->authenticator->verifyPermanentLogin();
		if ($token !== null) {
			$this->user->login($this->authenticator->getIdentity($token->getUserId(), $token->getUsername()));
			$this->authenticator->regeneratePermanentLogin($this->user);
			$this->restoreRequest($this->backlink);
			$this->redirect('Homepage:');
		}
		$this->template->pageTitle = 'Přihlásit se';
	}


	public function renderIn(): void
	{
		// generateAuthenticationOptions() writes a fresh challenge to the session each time the sign-in form is rendered,
		// so verifyAuthentication() always finds the challenge from the most recent render in the session
		$this->passkeyAuthOptions = $this->passkeyAuthenticator->generateAuthenticationOptions();
	}


	public function actionPasskeyAuthCanceled(): never
	{
		$this->flashMessage($this->translator->translate('messages.passkeys.authenticationCanceled'), 'error');
		$this->redirect('in');
	}


	public function actionPasskeyNotSupported(): never
	{
		$this->flashMessage($this->translator->translate('messages.passkeys.notSupported'), 'error');
		$this->redirect('in');
	}


	public function actionPasskeyResetCanceled(): never
	{
		$this->flashMessage($this->translator->translate('messages.passkeys.registrationCanceled'), 'error');
		$this->redirect('in');
	}


	public function actionPasskeyAuthError(): never
	{
		$this->flashMessage($this->translator->translate('messages.passkeys.authenticationFailed'), 'error');
		$this->redirect('in');
	}


	public function actionPasskeyReset(): void
	{
		$this->addPermissionsPolicy(PermissionsPolicyDirective::PublicKeyCredentialsCreate, PermissionsPolicyOrigin::Self);
		$this->template->pageTitle = $this->translator->translate('messages.passkeys.registration');
	}


	public function actionPasskeyResetOptions(): never
	{
		if (!$this->getHttpRequest()->isMethod(IRequest::Post)) {
			throw new BadRequestException('POST haste, GET lost', IResponse::S405_MethodNotAllowed);
		}
		$token = $this->httpInput->getPostString('token');
		if ($token === null) {
			throw new BadRequestException('Missing token', IResponse::S400_BadRequest);
		}
		try {
			$options = $this->passkeyReset->generateRegistrationOptions($token);
		} catch (PasskeyResetException) {
			throw new BadRequestException('Invalid or expired token', IResponse::S403_Forbidden);
		}
		$this->sendJsonString($options);
	}


	public function actionPasskeyResetError(): never
	{
		$this->flashMessage($this->translator->translate('messages.passkeys.registrationFailed'), 'error');
		$this->redirect('in');
	}


	public function actionOut(): never
	{
		$this->authenticator->clearPermanentLogin($this->user);
		$this->user->logout();
		$this->flashMessage('Byli jste odhlášeni');
		$this->redirect('in');
	}


	protected function createComponentSignInHoneypot(): UiForm
	{
		return $this->signInHoneypotFormFactory->create();
	}


	protected function createComponentPasskeyAuthenticate(): UiForm
	{
		return $this->passkeyAuthenticateFormFactory->create(
			function (): void {
				$this->restoreRequest($this->backlink);
				$this->redirect('Homepage:');
			},
			$this->link('passkey-auth-error'),
			$this->link('passkey-auth-canceled'),
			$this->passkeyAuthOptions,
		);
	}


	protected function createComponentPasskeyReset(): UiForm
	{
		return $this->passkeyResetFormFactory->create(
			function (): void {
				$this->redirect('in');
			},
			$this->link('passkey-reset-error'),
			$this->link('passkey-reset-canceled'),
			$this->link('passkey-not-supported'),
		);
	}

}
