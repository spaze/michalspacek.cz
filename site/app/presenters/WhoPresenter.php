<?php
/**
 * Who presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class WhoPresenter extends BasePresenter
{


	public function renderDefault()
	{
		$this->template->pageTitle  = 'Kdo?';
		$this->template->pageHeader = 'Kdo je Michal Špaček?';
	}


}