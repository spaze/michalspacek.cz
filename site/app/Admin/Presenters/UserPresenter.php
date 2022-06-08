<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Form\ChangePasswordFormFactory;
use MichalSpacekCz\Form\RegenerateTokensFormFactory;
use Nette\Forms\Form;
use Nette\Utils\Html;

class UserPresenter extends BasePresenter
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


	protected function createComponentChangePassword(): Form
	{
		return $this->changePasswordFormFactory->create(
			function (): void {
				$this->redirect('Homepage:');
			},
		);
	}


	public function actionRegenerateTokens(): void
	{
		$this->template->pageTitle = 'Přegenerovat tokeny';
	}


	protected function createComponentRegenerateTokens(): Form
	{
		return $this->regenerateTokensFormFactory->create(
			function (Html|string $message): void {
				$this->flashMessage($message);
				$this->redirect('Homepage:');
			},
		);
	}

}
