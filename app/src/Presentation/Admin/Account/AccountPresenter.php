<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Account;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\User\AccountEmailFormFactory;
use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyDirective;
use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyOrigin;
use MichalSpacekCz\Presentation\Admin\BasePresenter;
use Nette\Application\UI\Form;

final class AccountPresenter extends BasePresenter
{

	public function __construct(
		private readonly AccountEmailFormFactory $accountEmailFormFactory,
		private readonly Translator $translator,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.account.title');
		$this->addPermissionsPolicy(PermissionsPolicyDirective::PublicKeyCredentialsGet, PermissionsPolicyOrigin::Self);
	}


	protected function createComponentEmail(): Form
	{
		return $this->accountEmailFormFactory->create(
			function (): void {
				$this->flashMessage($this->translator->translate('messages.account.email.saved'));
				$this->redirect('this');
			},
		);
	}

}
