<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\User;

use MichalSpacekCz\Form\ChangePasswordFormFactory;
use MichalSpacekCz\Form\RegenerateTokensFormFactory;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\Presentation\Admin\BasePresenter;
use Nette\Utils\Html;

final class UserPresenter extends BasePresenter
{

	public function __construct(
		private readonly RegenerateTokensFormFactory $regenerateTokensFormFactory,
		private readonly ChangePasswordFormFactory $changePasswordFormFactory,
	) {
		parent::__construct();
	}


	public function actionChangePassword(): void
	{
		$this->template->pageTitle = 'Změnit heslo';
	}


	protected function createComponentChangePassword(): UiForm
	{
		return $this->changePasswordFormFactory->create(
			function (): never {
				$this->redirect('Homepage:');
			},
		);
	}


	public function actionRegenerateTokens(): void
	{
		$this->template->pageTitle = 'Přegenerovat tokeny';
	}


	protected function createComponentRegenerateTokens(): UiForm
	{
		return $this->regenerateTokensFormFactory->create(
			function (Html|string $message): never {
				$this->flashMessage($message);
				$this->redirect('Homepage:');
			},
		);
	}

}
