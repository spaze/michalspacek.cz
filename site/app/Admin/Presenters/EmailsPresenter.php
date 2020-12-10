<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Form\TrainingMailsOutbox;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Mails;
use MichalSpacekCz\Training\Statuses;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Database\Row;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;
use stdClass;

class EmailsPresenter extends BasePresenter
{

	/** @var Applications */
	protected $trainingApplications;

	/** @var Mails */
	protected $trainingMails;

	/** @var Statuses */
	protected $trainingStatuses;

	/** @var Row[] */
	private $applications;


	public function __construct(Applications $trainingApplications, Mails $trainingMails, Statuses $trainingStatuses)
	{
		$this->trainingApplications = $trainingApplications;
		$this->trainingMails = $trainingMails;
		$this->trainingStatuses = $trainingStatuses;
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$this->template->pageTitle = 'E-maily k odeslání';
		$this->applications = $this->trainingMails->getApplications();
		$this->template->applications = $this->applications;
	}


	protected function createComponentMails(string $formName): TrainingMailsOutbox
	{
		$form = new TrainingMailsOutbox($this, $formName, $this->applications);
		$form->onSuccess[] = [$this, 'submittedMails'];
		return $form;
	}


	/**
	 * @param Form $form
	 * @param ArrayHash<integer|string> $values
	 */
	public function submittedMails(Form $form, ArrayHash $values): void
	{
		$sent = 0;
		/** @var stdClass $data */
		foreach ($values->applications as $id => $data) {
			if (empty($data->send) || !isset($this->applications[$id])) {
				continue;
			}
			/** @var Template $template */
			$template = $this->createTemplate();
			$additional = trim($data->additional);

			if ($this->applications[$id]->nextStatus === Statuses::STATUS_INVITED) {
				$this->trainingMails->sendInvitation($this->applications[$id], $template, $additional);
				$this->trainingStatuses->updateStatus($id, Statuses::STATUS_INVITED);
				$sent++;
			}

			if ($this->applications[$id]->nextStatus === Statuses::STATUS_MATERIALS_SENT) {
				$this->trainingMails->sendMaterials($this->applications[$id], $template, $data->feedbackRequest ?? false, $additional);
				$this->trainingStatuses->updateStatus($id, Statuses::STATUS_MATERIALS_SENT);
				$sent++;
			}

			if (in_array($this->applications[$id]->nextStatus, [Statuses::STATUS_INVOICE_SENT, Statuses::STATUS_INVOICE_SENT_AFTER])) {
				if ($data->invoice->isOk()) {
					$this->trainingApplications->updateApplicationInvoiceData($id, $data->invoiceId);
					$this->applications[$id]->invoiceId = $data->invoiceId;
					$this->trainingMails->sendInvoice($this->applications[$id], $template, $data->invoice, $additional);
					$this->trainingStatuses->updateStatus($id, $this->applications[$id]->nextStatus);
					$sent++;
				}
			}

			if ($this->applications[$id]->nextStatus === Statuses::STATUS_REMINDED) {
				$this->trainingMails->sendReminder($this->applications[$id], $template, $additional);
				$this->trainingStatuses->updateStatus($id, Statuses::STATUS_REMINDED);
				$sent++;
			}
		}
		if ($sent) {
			$this->flashMessage('Počet odeslaných e-mailů: ' . $sent);
		} else {
			$this->flashMessage('Nebyl odeslán žádný e-mail', 'notice');
		}
		$this->redirect('Homepage:');
	}

}
