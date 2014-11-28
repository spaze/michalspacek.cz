<?php
namespace AdminModule;

use \MichalSpacekCz\TrainingApplications;

/**
 * Emails presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class EmailsPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\TrainingApplications */
	protected $trainingApplications;

	/** @var \MichalSpacekCz\TrainingMails */
	protected $trainingMails;

	/** @var \MichalSpacekCz\Vat */
	protected $vat;

	/** @var array */
	private $applications;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\TrainingApplications $trainingApplications
	 * @param \MichalSpacekCz\TrainingMails $trainingMails
	 * @param \MichalSpacekCz\Vat $vat
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		\MichalSpacekCz\TrainingApplications $trainingApplications,
		\MichalSpacekCz\TrainingMails $trainingMails,
		\MichalSpacekCz\Vat $vat
	)
	{
		$this->trainingApplications = $trainingApplications;
		$this->trainingMails = $trainingMails;
		$this->vat = $vat;
		parent::__construct($translator);
	}


	public function actionDefault()
	{
		$this->template->pageTitle = 'E-maily k odeslání';
		$this->applications = array();
		foreach ($this->trainingMails->getApplications() as $application) {
			$this->applications[$application->id] = $application;
		}
		$this->template->applications = $this->applications;
	}


	protected function createComponentMails($formName)
	{
		$form = new \MichalSpacekCz\Form\TrainingMailsOutbox($this, $formName, $this->applications);
		$form->onSuccess[] = $this->submittedMails;
	}


	public function submittedMails($form)
	{
		$values = $form->getValues();
		$sent = false;
		foreach ($values->applications as $id => $data) {
			if (empty($data->send) || !isset($this->applications[$id])) {
				continue;
			}
			$additional = trim($data->additional);

			if (in_array($this->applications[$id]->status, $this->trainingApplications->getParentStatuses(TrainingApplications::STATUS_INVITED))) {
				$this->trainingMails->sendInvitation($this->applications[$id], $this->createTemplate(), $additional);
				$this->trainingApplications->updateStatus($id, TrainingApplications::STATUS_INVITED);
				$sent = true;
			}

			if (in_array($this->applications[$id]->status, $this->trainingApplications->getParentStatuses(TrainingApplications::STATUS_MATERIALS_SENT))) {
				$this->trainingMails->sendMaterials($this->applications[$id], $this->createTemplate(), $additional);
				$this->trainingApplications->updateStatus($id, TrainingApplications::STATUS_MATERIALS_SENT);
				$sent = true;
			}

			if (in_array($this->applications[$id]->status, $this->trainingApplications->getParentStatuses(TrainingApplications::STATUS_INVOICE_SENT))) {
				if ($data->invoice->isOk()) {
					$this->trainingApplications->updateApplicationInvoiceData($id, $data->invoiceId);
					$this->applications[$id]->invoiceId = $data->invoiceId;

					$invoice = array($data->invoice->getName() => $data->invoice->getTemporaryFile());
					$this->trainingMails->sendInvoice($this->applications[$id], $this->createTemplate(), $invoice, $additional);

					$this->trainingApplications->updateStatus($id, TrainingApplications::STATUS_INVOICE_SENT);
					$sent = true;
				}
			}

			if (in_array($this->applications[$id]->status, $this->trainingApplications->getParentStatuses(TrainingApplications::STATUS_REMINDED))) {
				$this->trainingMails->sendReminder($this->applications[$id], $this->createTemplate(), $additional);
				$this->trainingApplications->updateStatus($id, TrainingApplications::STATUS_REMINDED);
				$sent = true;
			}
		}
		if ($sent) {
			$this->flashMessage('E-maily odeslány');
		} else {
			$this->flashMessage('Nebyl odeslán žádný e-mail', 'notice');
		}
		$this->redirect('Homepage:');
	}


}
