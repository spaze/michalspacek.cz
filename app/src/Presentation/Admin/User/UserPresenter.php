<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\User;

use MichalSpacekCz\Form\User\RegenerateTokensFormFactory;
use MichalSpacekCz\Presentation\Admin\BasePresenter;
use Nette\Forms\Form;
use Nette\Utils\Html;

final class UserPresenter extends BasePresenter
{

	public function __construct(
		private readonly RegenerateTokensFormFactory $regenerateTokensFormFactory,
	) {
		parent::__construct();
	}


	public function actionRegenerateTokens(): void
	{
		$this->template->pageTitle = 'Přegenerovat tokeny';
	}


	protected function createComponentRegenerateTokens(): Form
	{
		return $this->regenerateTokensFormFactory->create(
			function (Html|string $message): never {
				$this->flashMessage($message);
				$this->redirect('Homepage:');
			},
		);
	}

}
