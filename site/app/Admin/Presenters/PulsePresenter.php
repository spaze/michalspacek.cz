<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Form\Pulse\PasswordsStorageAlgorithmFormFactory;
use MichalSpacekCz\Form\UiForm;

class PulsePresenter extends BasePresenter
{

	private const NEW_DISCLOSURES = 3;


	public function __construct(
		private readonly PasswordsStorageAlgorithmFormFactory $passwordsStoragesFormFactory,
	) {
		parent::__construct();
	}


	public function actionPasswordsStorages(): void
	{
		$this->template->pageTitle = 'Password storages';
		$this->template->newDisclosures = self::NEW_DISCLOSURES;
	}


	protected function createComponentAddPasswordsStorageAlgorithm(): UiForm
	{
		return $this->passwordsStoragesFormFactory->create(
			function (?string $message): never {
				if ($message !== null) {
					$this->flashMessage($message);
				}
				$this->redirect('this');
			},
			self::NEW_DISCLOSURES,
		);
	}

}
