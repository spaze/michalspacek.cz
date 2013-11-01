<?php
namespace AdminModule;

use \MichalSpacekCz\TrainingApplications,
	\MichalSpacekCz\TrainingDates;

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
		foreach ($values->applications as $id => $send) {
			if (!$send || !isset($this->applications[$id])) {
				continue;
			}
			switch ($this->applications[$id]->status) {
				case \MichalSpacekCz\TrainingApplications::STATUS_TENTATIVE:
					$this->trainingMails->sendInvitation($this->applications[$id], $this->createTemplate());
					break;
				case \MichalSpacekCz\TrainingApplications::STATUS_ATTENDED:
					$this->trainingMails->sendMaterials($this->applications[$id], $this->createTemplate());
					break;
			}
		}
		$this->flashMessage('E-maily odeslány');
		$this->redirect('Homepage:');
	}


}
