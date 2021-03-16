<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Form\TrainingMailsOutboxFactory;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Mails;
use MichalSpacekCz\Training\Statuses;
use Nette\Application\UI\Form;
use Nette\Database\Row;

class EmailsPresenter extends BasePresenter
{

	private Applications $trainingApplications;

	private Mails $trainingMails;

	private Statuses $trainingStatuses;

	private TrainingMailsOutboxFactory $trainingMailsOutboxFactory;

	/** @var Row[] */
	private array $applications;


	public function __construct(Applications $trainingApplications, Mails $trainingMails, Statuses $trainingStatuses, TrainingMailsOutboxFactory $trainingMailsOutboxFactory)
	{
		$this->trainingApplications = $trainingApplications;
		$this->trainingMails = $trainingMails;
		$this->trainingStatuses = $trainingStatuses;
		$this->trainingMailsOutboxFactory = $trainingMailsOutboxFactory;
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$this->template->pageTitle = 'E-maily k odeslání';
		$this->applications = $this->trainingMails->getApplications();
		$this->template->applications = $this->applications;
	}


	protected function createComponentMails(): Form
	{
		return $this->trainingMailsOutboxFactory->create(
			function (int $sent): void {
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
