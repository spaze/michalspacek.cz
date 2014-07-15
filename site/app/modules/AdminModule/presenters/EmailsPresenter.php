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

	/** @var array */
	private $applications;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\TrainingApplications $trainingApplications
	 * @param \MichalSpacekCz\TrainingMails $trainingMails
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		\MichalSpacekCz\TrainingApplications $trainingApplications,
		\MichalSpacekCz\TrainingMails $trainingMails
	)
	{
		$this->trainingApplications = $trainingApplications;
		$this->trainingMails = $trainingMails;
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
			switch ($this->applications[$id]->status) {
				case TrainingApplications::STATUS_TENTATIVE:
					$this->trainingMails->sendInvitation($this->applications[$id], $this->createTemplate(), $additional);
					$this->trainingApplications->updateStatus($id, TrainingApplications::STATUS_INVITED);
					$sent = true;
					break;
				case TrainingApplications::STATUS_ATTENDED:
					$this->trainingMails->sendMaterials($this->applications[$id], $this->createTemplate(), $additional);
					$this->trainingApplications->updateStatus($id, TrainingApplications::STATUS_MATERIALS_SENT);
					$sent = true;
					break;
				case TrainingApplications::STATUS_SIGNED_UP:
					if ($data->invoice->isOk()) {
						$this->trainingApplications->updateApplicationInvoiceData($id, $data->price, $data->discount, $data->invoiceId);
						$this->applications[$id]->price = $data->price;
						$this->applications[$id]->discount = $data->discount;
						$this->applications[$id]->invoiceId = $data->invoiceId;

						$invoice = array($data->invoice->getName() => $data->invoice->getTemporaryFile());
						$this->trainingMails->sendInvoice($this->applications[$id], $this->createTemplate(), $invoice, $additional);

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
