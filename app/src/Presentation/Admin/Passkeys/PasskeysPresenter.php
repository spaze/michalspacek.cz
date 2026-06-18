<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Passkeys;

use Contributte\Translation\Translator;
use InvalidArgumentException;
use MichalSpacekCz\Form\User\PasskeyDeleteFormFactory;
use MichalSpacekCz\Form\User\PasskeyRegistrationFormFactory;
use MichalSpacekCz\Form\User\PasskeyRenameFormFactory;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Http\HttpInput;
use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyDirective;
use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyOrigin;
use MichalSpacekCz\Presentation\Admin\BasePresenter;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyCredentialNotFoundException;
use MichalSpacekCz\User\WebAuthn\Passkeys;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationDisabledException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationInvalidOrExpiredTokenException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationUserMismatchException;
use MichalSpacekCz\User\WebAuthn\Registration\PasskeyAdd;
use Nette\Application\BadRequestException;
use Nette\Forms\Form;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Symfony\Component\Uid\Uuid;

final class PasskeysPresenter extends BasePresenter
{

	private ?Uuid $passkeyId = null;
	private ?string $passkeyCurrentName = null;


	public function __construct(
		private readonly PasskeyRenameFormFactory $passkeyRenameFormFactory,
		private readonly PasskeyDeleteFormFactory $passkeyDeleteFormFactory,
		private readonly Passkeys $passkeys,
		private readonly Translator $translator,
		private readonly TexyFormatter $texyFormatter,
		private readonly PasskeyAdd $passkeyRegistration,
		private readonly PasskeyRegistrationFormFactory $passkeyRegistrationFormFactory,
		private readonly HttpInput $httpInput,
	) {
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$this->template->setParameters(new PasskeysDefaultTemplateParameters(
			$this->translator->translate('messages.passkeys.manage.yourPasskeys'),
			$this->passkeys->getPasskeys(),
		));
	}


	public function actionRegister(): void
	{
		$this->template->setParameters(new PasskeysRegisterTemplateParameters(
			$this->translator->translate('messages.passkeys.registerPasskey'),
			$this->passkeyRegistration->isEnabled(),
		));
	}


	public function actionAdd(): void
	{
		$this->addPermissionsPolicy(PermissionsPolicyDirective::PublicKeyCredentialsCreate, PermissionsPolicyOrigin::Self);
		$this->template->pageTitle = $this->translator->translate('messages.passkeys.add.addPasskey');
	}


	public function actionAddOptions(): never
	{
		if (!$this->getHttpRequest()->isMethod(IRequest::Post)) {
			throw new BadRequestException('POST haste, GET lost', IResponse::S405_MethodNotAllowed);
		}
		$token = $this->httpInput->getPostString('token');
		if ($token === null) {
			throw new BadRequestException('Missing token', IResponse::S400_BadRequest);
		}
		try {
			$options = $this->passkeyRegistration->generateRegistrationOptions($token);
		} catch (PasskeyRegistrationDisabledException | PasskeyRegistrationInvalidOrExpiredTokenException | PasskeyRegistrationUserMismatchException) {
			throw new BadRequestException('Invalid or expired token', IResponse::S403_Forbidden);
		}
		$this->sendJsonString($options);
	}


	public function actionAddCanceled(): never
	{
		$this->flashMessage($this->translator->translate('messages.passkeys.registrationCanceled'), 'error');
		$this->redirect('Passkeys:');
	}


	public function actionAddError(): never
	{
		$this->flashMessage($this->translator->translate('messages.passkeys.registrationFailed'), 'error');
		$this->redirect('Passkeys:');
	}


	public function actionRename(string $id): void
	{
		try {
			$this->passkeyId = Uuid::fromRfc4122($id);
			$this->passkeyCurrentName = $this->passkeys->getCredentialNameById($this->passkeyId);
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
			$this->passkeyCurrentName = $this->passkeys->getCredentialNameById($this->passkeyId);
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


	protected function createComponentPasskeyAdd(): Form
	{
		return $this->passkeyRegistrationFormFactory->create(
			function (): void {
				$this->flashMessage($this->translator->translate('messages.passkeys.registered'));
				$this->redirect('Passkeys:');
			},
			$this->link('add-options'),
			$this->link('add-error'),
			$this->link('add-canceled'),
			$this->link('Sign:passkey-not-supported'),
		);
	}


	protected function createComponentPasskeyRename(): Form
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


	protected function createComponentPasskeyDelete(): Form
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
