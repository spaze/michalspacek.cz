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


	public function actionDefault()
	{
		$this->template->pageTitle = 'E-maily k odeslání';

		$this->template->applications = $this->trainingApplications->getByStatus(TrainingApplications::STATUS_ATTENDED);
		foreach ($this->template->applications as $application) {
			$application->files = $this->trainingApplications->getFiles($application->id);
		}

		foreach ($this->trainingApplications->getByStatus(TrainingApplications::STATUS_TENTATIVE) as $application) {
			if ($this->trainingDates->get($application->dateId)->status == TrainingDates::STATUS_CONFIRMED) {
				$this->template->applications[] = $application;
			}
		}
	}


}
