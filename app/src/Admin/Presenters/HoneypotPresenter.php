<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Form\SignInHoneypotFormFactory;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\Www\Presenters\BasePresenter;

class HoneypotPresenter extends BasePresenter
{

	public function __construct(
		private readonly SignInHoneypotFormFactory $signInHoneypotFormFactory,
	) {
		parent::__construct();
	}


	public function actionSignIn(): void
	{
		$this->template->pageTitle = 'Přihlásit se';
	}


	protected function createComponentSignIn(): UiForm
	{
		return $this->signInHoneypotFormFactory->create();
	}

}
