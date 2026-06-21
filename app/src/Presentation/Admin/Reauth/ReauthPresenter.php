<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Reauth;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\User\PasskeyReauthenticateFormFactory;
use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyDirective;
use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyOrigin;
use MichalSpacekCz\Presentation\Admin\BasePresenter;
use Nette\Forms\Form;

/**
 * Asks the user to confirm their identity with a passkey before viewing a sensitive page. Reached only
 * from requireReauthentication(); once confirmed, the user is sent back to where they came from. This
 * page must not call requireReauthentication() itself or it would keep sending the user to itself.
 */
final class ReauthPresenter extends BasePresenter
{

	/** @persistent */
	public string $backlink = '';


	public function __construct(
		private readonly PasskeyReauthenticateFormFactory $passkeyReauthenticateFormFactory,
		private readonly Translator $translator,
	) {
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$this->addPermissionsPolicy(PermissionsPolicyDirective::PublicKeyCredentialsGet, PermissionsPolicyOrigin::Self);
		$this->template->pageTitle = $this->translator->translate('messages.reauth.title');
	}


	public function actionError(): never
	{
		$this->flashMessage($this->translator->translate('messages.passkeys.authenticationFailed'), 'error');
		$this->redirect('default');
	}


	public function actionCanceled(): never
	{
		$this->flashMessage($this->translator->translate('messages.passkeys.authenticationCanceled'), 'error');
		$this->redirect('default');
	}


	protected function createComponentPasskeyReauthenticate(): Form
	{
		return $this->passkeyReauthenticateFormFactory->create(
			function (): void {
				$this->restoreRequest($this->backlink);
				$this->redirect(':Admin:Homepage:'); // fallback when the stored request can't be restored
			},
			$this->link('error'),
			$this->link('canceled'),
		);
	}

}
