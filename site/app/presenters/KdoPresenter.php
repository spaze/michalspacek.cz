<?php
/**
 * Kdo presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class KdoPresenter extends BasePresenter
{


	public function renderDefault()
	{
		$this->template->pageTitle  = 'Kdo?';
		$this->template->pageHeader = 'Kdo je Michal Špaček?';
	}


}