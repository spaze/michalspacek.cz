<?php
/**
 * Články presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ClankyPresenter extends BasePresenter
{


	public function renderDefault()
	{
		$this->template->pageTitle = 'Články';
		$this->template->articles  = $this->getService('articles')->getAll();
	}


}
