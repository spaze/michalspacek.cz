<?php
/**
 * Homepage presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HomepagePresenter extends BasePresenter
{


	public function renderDefault()
	{
		$this->template->articles          = $this->getService('articles')->getAll(3);
		$this->template->talks             = $this->getService('talks')->getAll(5);
		$this->template->upcomingTrainings = $this->getService('trainings')->getUpcoming();
	}


}
