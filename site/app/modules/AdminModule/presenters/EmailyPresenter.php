<?php
namespace AdminModule;

use \MichalSpacekCz\Trainings;

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

		$this->template->applications = $this->trainingApplications->getByStatus(Trainings::STATUS_ATTENDED);
	}


	public function renderDefault()
	{
		$this->template->pageTitle = 'Emaily';
	}


}
