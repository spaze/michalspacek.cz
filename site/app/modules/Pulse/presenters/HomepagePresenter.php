<?php
namespace App\PulseModule\Presenters;

/**
 * Homepage presenter.
 *
 * @author Michal Špaček
 * @package pulse.michalspacek.cz
 */
class HomepagePresenter extends \App\WwwModule\Presenters\BasePresenter
{

	/**
	 * Default action handler.
	 */
	public function actionDefault()
	{
		$this->template->pageTitle = null;
		$this->template->pageHeader = 'Pulse';
	}

}
