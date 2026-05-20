<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Passkeys;

use Contributte\Translation\Translator;
use InvalidArgumentException;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\Form\User\PasskeyDeleteFormFactory;
use MichalSpacekCz\Form\User\PasskeyRegisterFormFactory;
use MichalSpacekCz\Form\User\PasskeyRenameFormFactory;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyDirective;
use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyOrigin;
use MichalSpacekCz\Presentation\Admin\BasePresenter;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialNotFoundException;
use MichalSpacekCz\User\WebAuthn\UserPasskeys;
use MichalSpacekCz\User\WebAuthn\WebAuthnAuthenticator;
use Nette\Application\BadRequestException;
use Nette\Security\User;
use Symfony\Component\Uid\Uuid;

final class PasskeysPresenter extends BasePresenter
{

	private ?string $passkeyRegisterOptions = null;
	private ?Uuid $passkeyId = null;
	private ?string $passkeyCurrentName = null;


	public function __construct(
		private readonly User $user,
		private readonly PasskeyRegisterFormFactory $passkeyRegisterFormFactory,
		private readonly PasskeyRenameFormFactory $passkeyRenameFormFactory,
		private readonly PasskeyDeleteFormFactory $passkeyDeleteFormFactory,
		private readonly Manager $authenticator,
		private readonly WebAuthnAuthenticator $passkeyAuthenticator,
		private readonly UserPasskeys $userPasskeys,
		private readonly Translator $translator,
		private readonly TexyFormatter $texyFormatter,
	) {
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$this->template->setParameters(new PasskeysDefaultTemplateParameters(
			$this->translator->translate('messages.passkeys.manage.yourPasskeys'),
			$this->userPasskeys->getPasskeys(),
		));
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


	public function actionRename(string $id): void
	{
		try {
			$this->passkeyId = Uuid::fromRfc4122($id);
			$this->passkeyCurrentName = $this->userPasskeys->getCredentialNameById($this->passkeyId);
		} catch (InvalidArgumentException $e) {
			throw new BadRequestException("Invalid passkey id: $id", previous: $e);
		} catch (PasskeyCredentialNotFoundException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}
		$this->template->setParameters(new PasskeysRenameTemplateParameters(
			$this->translator->translate('messages.passkeys.rename.renamePasskey'),
			$this->passkeyCurrentName,
		));
	}


	public function actionDelete(string $id): void
	{
		try {
			$this->passkeyId = Uuid::fromRfc4122($id);
			$this->passkeyCurrentName = $this->userPasskeys->getCredentialNameById($this->passkeyId);
		} catch (InvalidArgumentException $e) {
			throw new BadRequestException("Invalid passkey id: $id", previous: $e);
		} catch (PasskeyCredentialNotFoundException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}
		$this->template->setParameters(new PasskeysDeleteTemplateParameters(
			$this->translator->translate('messages.passkeys.delete.deletePasskey'),
			$this->passkeyCurrentName,
		));
	}


	protected function createComponentPasskeyRegister(): UiForm
	{
		return $this->passkeyRegisterFormFactory->create(
			function (): void {
				$this->flashMessage($this->translator->translate('messages.passkeys.registered'));
				$this->redirect('Passkeys:');
			},
			$this->user,
			$this->link('register-error'),
			$this->link('register-canceled'),
			$this->link('Sign:passkey-not-supported'),
			$this->passkeyRegisterOptions,
		);
	}


	protected function createComponentPasskeyRename(): UiForm
	{
		if ($this->passkeyId === null || $this->passkeyCurrentName === null) {
			throw new ShouldNotHappenException('actionRename() will be called first');
		}
		return $this->passkeyRenameFormFactory->create(
			function (): void {
				$this->flashMessage($this->translator->translate('messages.passkeys.rename.renamed'));
				$this->redirect('Passkeys:');
			},
			function (): void {
				$this->flashMessage($this->translator->translate('messages.passkeys.manage.notFound'), 'error');
				$this->redirect('Passkeys:');
			},
			$this->passkeyId,
			$this->passkeyCurrentName,
		);
	}


	protected function createComponentPasskeyDelete(): UiForm
	{
		$passkeyCurrentName = $this->passkeyCurrentName;
		if ($this->passkeyId === null || $passkeyCurrentName === null) {
			throw new ShouldNotHappenException('actionDelete() will be called first');
		}
		return $this->passkeyDeleteFormFactory->create(
			function () use ($passkeyCurrentName): void {
				$this->flashMessage($this->texyFormatter->translate('messages.passkeys.delete.deleted', [$passkeyCurrentName]));
				$this->redirect('Passkeys:');
			},
			function (): void {
				$this->flashMessage($this->translator->translate('messages.passkeys.delete.cannotDeleteSignedInWith'), 'error');
				$this->redirect('Passkeys:');
			},
			function (): void {
				$this->flashMessage($this->translator->translate('messages.passkeys.manage.notFound'), 'error');
				$this->redirect('Passkeys:');
			},
			$this->passkeyId,
		);
	}

}
