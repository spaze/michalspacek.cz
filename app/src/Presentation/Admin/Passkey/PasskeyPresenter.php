<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Passkey;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\Form\User\PasskeyRegisterFormFactory;
use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyDirective;
use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyOrigin;
use MichalSpacekCz\Presentation\Admin\BasePresenter;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\User\WebAuthn\WebAuthnAuthenticator;
use Nette\Security\User;

final class PasskeyPresenter extends BasePresenter
{

	private ?string $passkeyRegisterOptions = null;


	public function __construct(
		private readonly User $user,
		private readonly PasskeyRegisterFormFactory $passkeyRegisterFormFactory,
		private readonly Manager $authenticator,
		private readonly WebAuthnAuthenticator $passkeyAuthenticator,
		private readonly Translator $translator,
	) {
		parent::__construct();
	}


	public function actionRegister(): void
	{
		$this->addPermissionsPolicy(PermissionsPolicyDirective::PublicKeyCredentialsCreate, PermissionsPolicyOrigin::Self);
		$this->template->pageTitle = $this->translator->translate('messages.passkeys.registerPasskey');
	}


	public function renderRegister(): void
	{
		// generateRegistrationOptions() writes a fresh challenge to the session each time the form is rendered,
		// so verifyRegistration() always finds the challenge from the most recent render in the session
		$this->passkeyRegisterOptions = $this->passkeyAuthenticator->generateRegistrationOptions(
			(int)$this->user->getId(),
			$this->authenticator->getIdentityUsernameByUser($this->user),
		);
	}


	public function actionRegisterCanceled(): never
	{
		$this->flashMessage($this->translator->translate('messages.passkeys.registrationCanceled'), 'error');
		$this->redirect('register');
	}


	public function actionRegisterError(): never
	{
		$this->flashMessage($this->translator->translate('messages.passkeys.registrationFailed'), 'error');
		$this->redirect('register');
	}


	protected function createComponentPasskeyRegister(): UiForm
	{
		return $this->passkeyRegisterFormFactory->createRegisterForm(
			function (): void {
				$this->flashMessage($this->translator->translate('messages.passkeys.registered'));
				$this->redirect('this');
			},
			$this->user,
			$this->link('register-error'),
			$this->link('register-canceled'),
			$this->link('Sign:passkey-not-supported'),
			$this->passkeyRegisterOptions,
		);
	}

}
