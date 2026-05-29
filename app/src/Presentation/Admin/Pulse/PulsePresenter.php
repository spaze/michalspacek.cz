<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Pulse;

use MichalSpacekCz\Form\Pulse\PasswordsStorageAlgorithmFormFactory;
use MichalSpacekCz\Presentation\Admin\BasePresenter;
use Nette\Forms\Form;

final class PulsePresenter extends BasePresenter
{

	private const int NEW_DISCLOSURES = 3;


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
			function (string $message): never {
				$this->flashMessage($message);
				$this->redirect('this');
			},
			self::NEW_DISCLOSURES,
		);
	}

}
