<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Account;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\User\AccountNotificationEmailFormFactory;
use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyDirective;
use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy\PermissionsPolicyOrigin;
use MichalSpacekCz\Presentation\Admin\BasePresenter;
use MichalSpacekCz\User\SecurityActivity\SecurityActivity;
use Nette\Application\UI\Form;

final class AccountPresenter extends BasePresenter
{

	public function __construct(
		private readonly AccountNotificationEmailFormFactory $accountNotificationEmailFormFactory,
		private readonly Translator $translator,
		private readonly SecurityActivity $securityActivity,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.account.title');
		$this->addPermissionsPolicy(PermissionsPolicyDirective::PublicKeyCredentialsGet, PermissionsPolicyOrigin::Self);
	}


	public function renderSecurityLog(): void
	{
		$this->template->setParameters(new SecurityLogTemplateParameters(
			$this->translator->translate('messages.account.securityLog.title'),
			$this->securityActivity->getEventsForCurrentUser(),
		));
	}


	protected function createComponentNotificationEmail(): Form
	{
		return $this->accountNotificationEmailFormFactory->create(
			function (): void {
				$this->flashMessage($this->translator->translate('messages.account.notificationEmail.saved'));
				$this->redirect('this');
			},
		);
	}

}
