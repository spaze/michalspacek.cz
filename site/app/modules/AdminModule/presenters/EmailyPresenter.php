<?php
namespace AdminModule;

use \MichalSpacekCz\TrainingApplications;

/**
 * Emaily presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class EmailyPresenter extends BasePresenter
{

	/** @var array */
	private $applications;


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
		$form->onSuccess[] = new \Nette\Callback($this, 'submittedMails');
	}


	public function submittedMails($form)
	{
		$values = $form->getValues();
		$sent = false;
		foreach ($values->applications as $id => $data) {
			if (empty($data->send) || !isset($this->applications[$id])) {
				continue;
			}
			switch ($this->applications[$id]->status) {
				case TrainingApplications::STATUS_TENTATIVE:
					$this->trainingMails->sendInvitation($this->applications[$id], $this->createTemplate());
					$this->trainingApplications->updateStatus($id, TrainingApplications::STATUS_INVITED);
					$sent = true;
					break;
				case TrainingApplications::STATUS_ATTENDED:
					$this->trainingMails->sendMaterials($this->applications[$id], $this->createTemplate());
					$this->trainingApplications->updateStatus($id, TrainingApplications::STATUS_MATERIALS_SENT);
					$sent = true;
					break;
				case TrainingApplications::STATUS_SIGNED_UP:
					if ($data->invoice->isOk()) {
						$this->trainingApplications->updateApplicationInvoiceData($id, $data->price, $data->discount, $data->invoiceId);
						$this->trainingMails->sendInvoice($this->applications[$id], $this->createTemplate(), $data->invoice);
						$this->trainingApplications->updateStatus($id, TrainingApplications::STATUS_INVOICE_SENT);
						$sent = true;
					}
					break;
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
