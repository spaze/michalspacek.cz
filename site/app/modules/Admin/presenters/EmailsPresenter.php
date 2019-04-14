<?php
namespace App\AdminModule\Presenters;

use MichalSpacekCz\Training;

/**
 * Emails presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class EmailsPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Training\Applications */
	protected $trainingApplications;

	/** @var \MichalSpacekCz\Training\Mails */
	protected $trainingMails;

	/** @var \MichalSpacekCz\Training\Statuses */
	protected $trainingStatuses;

	/** @var \MichalSpacekCz\Vat */
	protected $vat;

	/** @var \Nette\Database\Row[] */
	private $applications;


	/**
	 * @param \MichalSpacekCz\Training\Applications $trainingApplications
	 * @param \MichalSpacekCz\Training\Mails $trainingMails
	 * @param \MichalSpacekCz\Training\Statuses $trainingStatuses
	 * @param \MichalSpacekCz\Vat $vat
	 */
	public function __construct(
		Training\Applications $trainingApplications,
		Training\Mails $trainingMails,
		Training\Statuses $trainingStatuses,
		\MichalSpacekCz\Vat $vat
	)
	{
		$this->trainingApplications = $trainingApplications;
		$this->trainingMails = $trainingMails;
		$this->trainingStatuses = $trainingStatuses;
		$this->vat = $vat;
		parent::__construct();
	}


	public function actionDefault()
	{
		$this->template->pageTitle = 'E-maily k odeslání';
		$this->applications = $this->trainingMails->getApplications();
		$this->template->applications = $this->applications;
	}


	protected function createComponentMails($formName)
	{
		$form = new \MichalSpacekCz\Form\TrainingMailsOutbox($this, $formName, $this->applications);
		$form->onSuccess[] = [$this, 'submittedMails'];
	}


	public function submittedMails(\MichalSpacekCz\Form\TrainingMailsOutbox $form, \Nette\Utils\ArrayHash $values)
	{
		$sent = 0;
		/** @var \stdClass $data */
		foreach ($values->applications as $id => $data) {
			if (empty($data->send) || !isset($this->applications[$id])) {
				continue;
			}
			/** @var \Nette\Bridges\ApplicationLatte\Template $template */
			$template = $this->createTemplate();
			$additional = trim($data->additional);

			if ($this->applications[$id]->nextStatus === Training\Statuses::STATUS_INVITED) {
				$this->trainingMails->sendInvitation($this->applications[$id], $template, $additional);
				$this->trainingStatuses->updateStatus($id, Training\Statuses::STATUS_INVITED);
				$sent++;
			}

			if ($this->applications[$id]->nextStatus === Training\Statuses::STATUS_MATERIALS_SENT) {
				$this->trainingMails->sendMaterials($this->applications[$id], $template, $data->feedbackRequest, $additional);
				$this->trainingStatuses->updateStatus($id, Training\Statuses::STATUS_MATERIALS_SENT);
				$sent++;
			}

			if (in_array($this->applications[$id]->nextStatus, [Training\Statuses::STATUS_INVOICE_SENT, Training\Statuses::STATUS_INVOICE_SENT_AFTER])) {
				if ($data->invoice->isOk()) {
					$this->trainingApplications->updateApplicationInvoiceData($id, $data->invoiceId);
					$this->applications[$id]->invoiceId = $data->invoiceId;
					$this->trainingMails->sendInvoice($this->applications[$id], $template, $data->invoice, $additional);
					$this->trainingStatuses->updateStatus($id, $this->applications[$id]->nextStatus);
					$sent++;
				}
			}

			if ($this->applications[$id]->nextStatus === Training\Statuses::STATUS_REMINDED) {
				$this->trainingMails->sendReminder($this->applications[$id], $template, $additional);
				$this->trainingStatuses->updateStatus($id, Training\Statuses::STATUS_REMINDED);
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
