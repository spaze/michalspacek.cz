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


	public function actionMaterialy()
	{
		$this->template->pageTitle = 'Materiály ze školení';

		$this->template->applications = $this->trainingApplications->getByStatus(TrainingApplications::STATUS_ATTENDED);

		foreach ($this->trainingApplications->getByStatus(TrainingApplications::STATUS_TENTATIVE) as $application) {
			if ($this->trainingDates->get($application->dateId)->status == TrainingDates::STATUS_CONFIRMED) {
				$this->template->applications[] = $application;
			}
		}
	}


	public function renderDefault()
	{
		$this->template->pageTitle = 'Emaily';
	}


}
