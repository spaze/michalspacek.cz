<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Form\Pulse\PasswordsStorageAlgorithmFormFactory;
use Nette\Forms\Form;

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


	protected function createComponentAddPasswordsStorageAlgorithm(): Form
	{
		return $this->passwordsStoragesFormFactory->create(
			function (?string $message): never {
				if ($message) {
					$this->flashMessage($message);
				}
				$this->redirect('this');
			},
			self::NEW_DISCLOSURES,
		);
	}

}
