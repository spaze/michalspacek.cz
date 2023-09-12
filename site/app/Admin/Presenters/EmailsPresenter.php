<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Form\TrainingMailsOutboxFormFactory;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\Mails\TrainingMails;

class EmailsPresenter extends BasePresenter
{

	/** @var list<TrainingApplication> */
	private array $applications = [];


	public function __construct(
		private readonly TrainingMails $trainingMails,
		private readonly TrainingMailsOutboxFormFactory $trainingMailsOutboxFactory,
	) {
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$this->applications = $this->trainingMails->getApplications();
		$this->template->pageTitle = 'E-maily k odeslání';
		$this->template->applications = $this->applications;
	}


	protected function createComponentMails(): UiForm
	{
		return $this->trainingMailsOutboxFactory->create(
			function (int $sent): never {
				if ($sent) {
					$this->flashMessage('Počet odeslaných e-mailů: ' . $sent);
				} else {
					$this->flashMessage('Nebyl odeslán žádný e-mail', 'notice');
				}
				$this->redirect('Homepage:');
			},
			$this->applications,
		);
	}

}
