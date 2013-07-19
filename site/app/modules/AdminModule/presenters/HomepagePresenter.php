<?php
namespace AdminModule;

/**
 * Homepage presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HomepagePresenter extends BasePresenter
{


	public function actionDefault()
	{
		$this->template->pageTitle = 'Administrace';
	}


}
