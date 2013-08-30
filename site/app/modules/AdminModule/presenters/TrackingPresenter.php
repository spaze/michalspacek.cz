<?php
namespace AdminModule;

/**
 * Tracking presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrackingPresenter extends BasePresenter
{


	public function actionEnable()
	{
		$this->webTracking->enable();
		$this->redirect('Homepage:');
	}


	public function actionDisable()
	{
		$this->webTracking->disable();
		$this->redirect('Homepage:');
	}


}
