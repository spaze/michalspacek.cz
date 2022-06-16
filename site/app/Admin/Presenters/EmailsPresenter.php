<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Form\TrainingMailsOutboxFormFactory;
use MichalSpacekCz\Training\Mails;
use Nette\Application\UI\Form;
use Nette\Database\Row;

class EmailsPresenter extends BasePresenter
{

	/** @var Row[] */
	private array $applications;


	public function __construct(
		private readonly Mails $trainingMails,
		private readonly TrainingMailsOutboxFormFactory $trainingMailsOutboxFactory,
	) {
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$applications = $this->trainingMails->getApplications();
		foreach ($applications as $application) {
			$application->mailMessage = $this->trainingMails->getMailMessage($application);
		}
		$this->template->pageTitle = 'E-maily k odeslání';
		$this->applications = $applications;
		$this->template->applications = $this->applications;
	}


	protected function createComponentMails(): Form
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
